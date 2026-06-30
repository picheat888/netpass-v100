// อ่านค่าจาก server ผ่าน data island (CSP-safe — ไม่มี inline executable JS)
const _NPREQ = JSON.parse(document.getElementById('np-req-data').textContent);
const NP_STOCK = _NPREQ.stock;
const NP_CSRF = _NPREQ.csrf;
const NP_REQ_URL = _NPREQ.reqUrl;
const NP_CSS = _NPREQ.css;
const NP_L = _NPREQ.l;
const NP_STEP_TITLES = _NPREQ.stepTitles;

(function () {
    'use strict';
    let step = 1, selLocId = null, selLocName = '', selDur = null, selDurLabel = '';

    const reqModal  = document.getElementById('requestModal');
    if (!reqModal) return;
    const btnNext   = document.getElementById('reqNext');
    const btnBack   = document.getElementById('reqBack');
    const btnConf   = document.getElementById('reqConfirm');
    const stepTitle = document.getElementById('reqStepTitle');
    const stepSub   = document.getElementById('reqStepSub');
    const accept    = document.getElementById('reqAcceptTerms');
    const guestList = document.getElementById('reqGuestList');
    const guestErr  = document.getElementById('reqGuestErr');
    const LAST_STEP = 5;
    let issuedTickets = [];

    function esc(s){ const d=document.createElement('div'); d.textContent=s==null?'':s; return d.innerHTML; }
    function wifiEsc(s){ return String(s||'').replace(/([\\;,:"])/g,'\\$1'); }
    function maxStock(){ return (NP_STOCK[selLocId] && NP_STOCK[selLocId][selDur]) || 0; }

    function showStep(n){
        step = n;
        document.querySelectorAll('.np-step-pane').forEach(p=>p.classList.add('d-none'));
        document.getElementById('reqStep'+n).classList.remove('d-none');
        // รีเซ็ต scroll กลับบนสุดทุกครั้งที่เปลี่ยน step (กัน scroll ค้างจาก step ก่อน)
        const body = document.querySelector('#requestModal .np-rq-body');
        if (body) body.scrollTop = 0;
        document.querySelectorAll('.np-step').forEach(s=>{
            const sn=parseInt(s.dataset.step);
            s.classList.toggle('active', sn===n);
            s.classList.toggle('done', sn<n);
        });
        const t=NP_STEP_TITLES[n];
        if(t){ stepTitle.textContent=t.title; stepSub.textContent=t.sub; }
        const isResult = n > LAST_STEP;
        btnBack.classList.toggle('d-none', n===1 || isResult);
        btnNext.classList.toggle('d-none', n>=LAST_STEP);
        btnConf.classList.toggle('d-none', n!==LAST_STEP);
        document.getElementById('reqPrintAll').classList.toggle('d-none', !isResult);
        document.getElementById('reqDone').classList.toggle('d-none', !isResult);
        if(isResult){ stepTitle.textContent=''; stepSub.textContent=''; }
        // โชว์ error + ปุ่ม Add ที่หัว เฉพาะ Step 4 (Guest details)
        document.getElementById('reqStepAction').classList.toggle('d-none', n !== 4);
        guestErr.classList.add('d-none');   // ซ่อน alert จำนวน guest เมื่อสลับ step (โชว์เฉพาะตอนกด Add เกินจำนวนใน step 4)
        updateNextState();
    }

    function updateNextState(){
        if(step===1) btnNext.disabled = !accept.checked;
        else if(step===2) btnNext.disabled = !selLocId;
        else if(step===3) btnNext.disabled = !selDur;
        else if(step===4) btnNext.disabled = guestList.querySelectorAll('.np-guest-row').length===0;
    }

    // Step 1: terms gate
    accept.addEventListener('change', updateNextState);

    // Step 2: location
    document.querySelectorAll('.np-loc-card:not(.np-disabled)').forEach(card=>{
        card.addEventListener('click', function(){
            document.querySelectorAll('.np-loc-card').forEach(c=>c.classList.remove('selected'));
            this.classList.add('selected');
            selLocId=parseInt(this.dataset.locId); selLocName=this.dataset.locName; selDur=null;
            updateNextState();
        });
    });

    // Step 3: duration stock + select
    function refreshDurStock(){
        const locStock = NP_STOCK[selLocId] || {};
        // duration ที่เลือกไว้ไม่มีสต็อกในพื้นที่นี้ → ยกเลิกการเลือก (เช่น เปลี่ยน location)
        if(selDur && !(locStock[selDur] > 0)){ selDur=null; selDurLabel=''; }
        document.querySelectorAll('.np-dur-card').forEach(card=>{
            const dur=card.dataset.dur, cnt=locStock[dur]||0;
            card.querySelector('.np-dur-cnt').textContent=cnt;
            card.classList.toggle('np-disabled', cnt===0);
            // ไฮไลต์ให้ตรงกับ selDur จริงเสมอ (กันไฮไลต์ค้างทั้งที่ selDur ถูกรีเซ็ต)
            card.classList.toggle('selected', dur===selDur);
        });
        updateNextState();
    }
    document.querySelectorAll('.np-dur-card').forEach(card=>{
        card.addEventListener('click', function(){
            if(this.classList.contains('np-disabled')) return;
            document.querySelectorAll('.np-dur-card').forEach(c=>c.classList.remove('selected'));
            this.classList.add('selected');
            selDur=this.dataset.dur; selDurLabel=this.querySelector('.np-dur-label').textContent;
            updateNextState();
        });
    });

    // Step 4: guest rows
    function guestRowHtml(i){
        // input + ที่ว่างสำหรับข้อความ validation ใต้ช่อง
        // name=...[] ใส่ไว้เพื่อ autofill/a11y (JS อ่านค่าด้วย class ไม่ใช่ name)
        function f(cls, ph, name){
            return '<input type="text" name="'+name+'" class="form-control form-control-sm '+cls+'" placeholder="'+esc(ph)+'">'
                 + '<div class="np-field-err"></div>';
        }
        return '<div class="np-guest-row">'
            + '<button type="button" class="np-guest-del" title="x"><i class="bi bi-x-lg"></i></button>'
            + '<div class="np-guest-no">'+NP_L.no.replace('{0}', i)+'</div>'
            + '<div class="row g-2">'
            + '<div class="col-6">'+f('g-first', NP_L.first, 'guest_first[]')+'</div>'
            + '<div class="col-6">'+f('g-last', NP_L.last, 'guest_last[]')+'</div>'
            + '<div class="col-12">'+f('g-phone', NP_L.phone, 'guest_phone[]')+'</div>'
            + '</div></div>';
    }
    function renumber(){
        guestList.querySelectorAll('.np-guest-row').forEach((row,idx)=>{
            row.querySelector('.np-guest-no').textContent = NP_L.no.replace('{0}', idx+1);
        });
    }
    const MAX_GUEST = 12;   // ขอได้สูงสุด 12 รายการต่อครั้ง
    function addGuest(){
        const n = guestList.querySelectorAll('.np-guest-row').length;
        // ครบ 12 รายการต่อครั้ง — เพิ่มไม่ได้
        if(n >= MAX_GUEST){ guestErr.textContent = NP_L.maxGuest.replace('{0}', MAX_GUEST); guestErr.classList.remove('d-none'); return; }
        // เกิน stock คงเหลือ — เพิ่มไม่ได้
        if(n >= maxStock()){ guestErr.textContent = NP_L.maxStock.replace('{0}', maxStock()); guestErr.classList.remove('d-none'); return; }
        guestErr.classList.add('d-none');
        guestList.insertAdjacentHTML('beforeend', guestRowHtml(n+1));
        updateNextState();
    }
    document.getElementById('reqAddGuest').addEventListener('click', addGuest);

    guestList.addEventListener('click', function(e){
        // ปุ่มลบแถว (เหลือแถวเดียวลบไม่ได้)
        const del=e.target.closest('.np-guest-del');
        if(!del) return;
        if(guestList.querySelectorAll('.np-guest-row').length<=1) return;
        del.closest('.np-guest-row').remove();
        renumber(); guestErr.classList.add('d-none'); updateNextState();
    });
    function collectGuests(){
        const out=[];
        guestList.querySelectorAll('.np-guest-row').forEach(row=>{
            out.push({
                firstname: row.querySelector('.g-first').value.trim(),
                lastname:  row.querySelector('.g-last').value.trim(),
                phone:     row.querySelector('.g-phone').value.trim()
            });
        });
        return out;
    }

    // ตรวจรูปแบบเบอร์โทรไทย: ขึ้นต้น 0 ตามด้วยตัวเลขรวม 9–10 หลัก (อนุญาตเว้นวรรค/ขีดคั่น)
    function isThaiPhone(v){ return /^0\d{8,9}$/.test(v.replace(/[\s-]/g, '')); }

    // validate รายช่องในการ์ด — ช่องว่าง/เบอร์ผิด format ขึ้นขอบแดง + ข้อความใต้ช่อง, แล้ว focus ช่องแรกที่ผิด
    function validateGuests(){
        guestErr.classList.add('d-none');   // ซ่อน alert รวม (ใช้ validation รายช่องในการ์ด)
        let firstBad = null;

        // Supplier — 1 ค่าต่อ request ใช้กับ guest ทุกคน บังคับกรอก
        const supInp = document.getElementById('reqSupplier');
        const supErr = supInp.nextElementSibling;
        if(!supInp.value.trim()){
            supInp.classList.add('is-invalid');
            if(supErr){ supErr.textContent = NP_L.required; supErr.classList.add('show'); }
            firstBad = supInp;
        } else {
            supInp.classList.remove('is-invalid');
            if(supErr){ supErr.textContent = ''; supErr.classList.remove('show'); }
        }

        guestList.querySelectorAll('.np-guest-row .form-control').forEach(inp=>{
            const err = inp.nextElementSibling;   // .np-field-err
            let msg = '';
            if(!inp.value.trim()){ msg = NP_L.required; }
            else if(inp.classList.contains('g-phone') && !isThaiPhone(inp.value)){ msg = NP_L.phoneInvalid; }
            if(msg){
                inp.classList.add('is-invalid');
                if(err){ err.textContent = msg; err.classList.add('show'); }
                if(!firstBad) firstBad = inp;
            } else {
                inp.classList.remove('is-invalid');
                if(err){ err.textContent = ''; err.classList.remove('show'); }
            }
        });

        // เช็คเบอร์โทรซ้ำข้ามแถว (กัน copy-paste คนเดียวกันหลายรายการ) — เทียบหลังตัดขีด/ช่องว่าง
        const seenPhone = {};
        guestList.querySelectorAll('.np-guest-row .g-phone').forEach(inp=>{
            const norm = inp.value.replace(/[\s-]/g, '');
            if(!norm || !isThaiPhone(inp.value)) return;   // ช่องว่าง/format ผิด จับโดย loop ด้านบนแล้ว
            if(seenPhone[norm]){
                // ทำให้ทั้งใบแรกและใบที่ซ้ำขึ้นแดงพร้อมข้อความ
                [seenPhone[norm], inp].forEach(bad=>{
                    bad.classList.add('is-invalid');
                    const e = bad.nextElementSibling;
                    if(e){ e.textContent = NP_L.dupPhone; e.classList.add('show'); }
                });
                if(!firstBad) firstBad = inp;
            } else {
                seenPhone[norm] = inp;
            }
        });

        // เช็คชื่อ-นามสกุลซ้ำข้ามแถว (normalize ตัดช่องว่าง + ตัวพิมพ์เล็ก)
        const seenName = {};
        guestList.querySelectorAll('.np-guest-row').forEach(row=>{
            const fIn = row.querySelector('.g-first'), lIn = row.querySelector('.g-last');
            const key = (fIn.value.trim() + '|' + lIn.value.trim()).toLowerCase().replace(/\s+/g, ' ');
            if(key === '|') return;   // ทั้งคู่ว่าง — จับโดย loop ด้านบนแล้ว
            if(seenName[key]){
                // ขึ้นแดงช่องชื่อ+นามสกุลของทั้งใบแรกและใบที่ซ้ำ
                [...seenName[key], fIn, lIn].forEach(bad=>{
                    bad.classList.add('is-invalid');
                    const e = bad.nextElementSibling;
                    if(e){ e.textContent = NP_L.dupName; e.classList.add('show'); }
                });
                if(!firstBad) firstBad = fIn;
            } else {
                seenName[key] = [fIn, lIn];
            }
        });

        if(firstBad){ firstBad.focus(); return false; }
        return true;
    }

    // พิมพ์แล้วล้างสถานะ error ของช่องนั้นทันที
    guestList.addEventListener('input', function(e){
        const inp = e.target;
        if(inp.classList.contains('form-control') && inp.value.trim()){
            inp.classList.remove('is-invalid');
            const err = inp.nextElementSibling;
            if(err){ err.textContent = ''; err.classList.remove('show'); }
        }
    });

    // ช่อง supplier อยู่นอก guestList — ล้าง error เมื่อเริ่มพิมพ์
    document.getElementById('reqSupplier').addEventListener('input', function(){
        if(this.value.trim()){
            this.classList.remove('is-invalid');
            const err = this.nextElementSibling;
            if(err){ err.textContent = ''; err.classList.remove('show'); }
        }
    });

    // Next / Back
    btnNext.addEventListener('click', function(){
        if(step===2){ refreshDurStock(); showStep(3); }
        else if(step===3){
            // เข้า step 4: ใส่แถว guest แถวแรกถ้ายังว่าง
            if(guestList.querySelectorAll('.np-guest-row').length===0) addGuest();
            showStep(4);
        }
        else if(step===4){
            // validate รายช่อง — ช่องว่างขึ้นแดง + focus ช่องแรก
            if(!validateGuests()) return;
            const guests=collectGuests();
            document.getElementById('confirmLoc').textContent=selLocName;
            document.getElementById('confirmDur').textContent=selDurLabel;
            document.getElementById('confirmCount').textContent=NP_L.countUnit.replace('{0}', guests.length);
            showStep(5);
        }
        else showStep(step+1);
    });
    btnBack.addEventListener('click', ()=>showStep(step-1));

    // Confirm submit
    btnConf.addEventListener('click', function(){
        const guests=collectGuests();
        btnConf.disabled=true;
        btnConf.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>...';
        const body=new URLSearchParams();
        body.append('location_id', selLocId);
        body.append('duration', selDur);
        body.append('supplier', document.getElementById('reqSupplier').value.trim());   // supplier ค่าเดียวต่อ request
        body.append(NP_CSRF.token, NP_CSRF.hash);
        guests.forEach((g,i)=>{
            body.append('guests['+i+'][firstname]', g.firstname);
            body.append('guests['+i+'][lastname]', g.lastname);
            body.append('guests['+i+'][phone]', g.phone);
        });
        fetch(NP_REQ_URL,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},body})
        .then(r=>r.json())
        .then(data=>{
            btnConf.disabled=false;
            btnConf.innerHTML='<i class="bi bi-check-circle me-1"></i>' + NP_L.btnConfirm;
            if(data.success){
                issuedTickets = data.tickets;
                renderResult(data.tickets);
                showStep(6);
                if(window.voucherDT) window.voucherDT.ajax.reload(null,false);
            } else { alert(data.message || NP_L.genErr); }
        })
        .catch(()=>{ btnConf.disabled=false; btnConf.innerHTML='<i class="bi bi-check-circle me-1"></i>' + NP_L.btnConfirm; alert(NP_L.genErr); });
    });

    // สร้างการ์ดตั๋ว (vm-ticket) + พิมพ์ 3 ใบ/แถว
    // SSID เปิด (auth ที่ captive portal) → QR แค่พาเข้า Wi-Fi เปลือยๆ; กรอก voucher ที่พอร์ทัล
    function qrSvg(ssid,pass){ const qr=qrcode(0,'M'); qr.addData('WIFI:T:nopass;S:'+wifiEsc(ssid)+';;'); qr.make(); return qr.createSvgTag({cellSize:4,margin:0,scalable:true}); }
    function buildTicket(t){
        return '<div class="vm-ticket-wrap"><div class="vm-ticket">'
            + '<div class="vm-ticket-main">'
            +   '<div class="vm-ticket-title"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="#3b7ddd" viewBox="0 0 16 16" style="flex-shrink:0"><path d="M15.384 6.115a.485.485 0 0 0-.047-.736A12.444 12.444 0 0 0 8 3C5.259 3 2.723 3.882.663 5.379a.485.485 0 0 0-.048.736.518.518 0 0 0 .668.05A11.448 11.448 0 0 1 8 4c2.507 0 4.827.802 6.716 2.164.205.148.49.13.668-.049z"/><path d="M13.229 8.271c.216-.216.194-.578-.063-.745A9.456 9.456 0 0 0 8 6c-1.905 0-3.68.56-5.166 1.526a.48.48 0 0 0-.063.745.525.525 0 0 0 .652.065A8.46 8.46 0 0 1 8 7c1.689 0 3.24.5 4.576 1.336.206.132.48.108.653-.065zm-2.183 2.183c.226-.226.185-.605-.1-.75A6.473 6.473 0 0 0 8 9c-1.06 0-2.062.254-2.946.704-.285.145-.326.524-.1.75l.015.015c.16.16.408.19.611.09A5.478 5.478 0 0 1 8 10c.764 0 1.49.156 2.15.435.203.1.45.07.611-.09l.085-.092zM9.06 12.44c.196-.196.198-.52-.04-.66A1.99 1.99 0 0 0 8 11.5a1.99 1.99 0 0 0-1.02.28c-.238.14-.236.464-.04.66l.706.706a.5.5 0 0 0 .707 0l.707-.707z"/></svg><span>'+esc(NP_L.cardTitle)+'</span></div>'
            +   '<div class="vm-meta">'
            +     '<div class="vm-meta-col"><div class="vm-meta-label">'+esc(NP_L.lblWifi)+'</div><div class="vm-meta-val font-mono">'+esc(t.ssid)+'</div></div>'
            +     '<div class="vm-meta-col"><div class="vm-meta-label">'+esc(NP_L.colLoc)+'</div><div class="vm-meta-val text-truncate">'+esc(t.location)+'</div></div>'
            +   '</div>'
            +   '<div class="vm-creds">'
            +     '<div class="vm-cred"><div class="vm-cred-top"><span class="vm-cred-label">USERNAME</span></div><div class="vm-cred-val font-mono">'+esc(t.username)+'</div></div>'
            +     '<div class="vm-cred"><div class="vm-cred-top"><span class="vm-cred-label">PASSWORD</span></div><div class="vm-cred-val font-mono">'+esc(t.password)+'</div></div>'
            +   '</div>'
            +   '<div class="vm-foot"><span>'+esc(NP_L.colDur)+' <b>'+esc(t.duration)+'</b></span><span>'+esc(NP_L.colExp)+' <b>'+esc(t.expires_at)+'</b></span></div>'
            + '</div>'
            + '<div class="vm-ticket-qr"><div class="vm-qr-name font-mono">'+esc(t.ssid)+'</div><div class="vm-qr">'+qrSvg(t.ssid,t.password)+'</div><div class="vm-qr-cap">'+esc(NP_L.scan)+'</div></div>'
            + '</div></div>';
    }
    function printTickets(tickets){
        const cards = tickets.map(buildTicket).join('');
        const frame = document.createElement('iframe');
        frame.style.cssText='position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
        frame.onload=function(){ frame.contentWindow.focus(); frame.contentWindow.print(); setTimeout(()=>frame.remove(),1500); };
        frame.srcdoc='<!doctype html><html lang="th"><head><meta charset="utf-8">'
            +'<link rel="stylesheet" href="'+NP_CSS.fonts+'"><link rel="stylesheet" href="'+NP_CSS.icons+'"><link rel="stylesheet" href="'+NP_CSS.main+'">'
            +'<style>*{-webkit-print-color-adjust:exact;print-color-adjust:exact;}body{margin:0;background:#fff;}'
            +'.np-print-sheet{display:flex;flex-wrap:wrap;gap:5mm 4mm;align-content:flex-start;}'
            +'.np-print-sheet .vm-ticket-wrap{padding:0;width:600px;zoom:0.34;break-inside:avoid;}'
            +'.np-dot{display:none;}'
            +'@page{margin:1.27cm;size:A4 portrait;}'
            +'</style></head><body><div class="np-print-sheet">'+cards+'</div></body></html>';
        document.body.appendChild(frame);
    }

    // แสดงรายการที่ออกในหน้าผลลัพธ์ (step 6) + ผูกปุ่มพิมพ์
    function renderResult(tickets){
        document.getElementById('reqResultCount').textContent = NP_L.countUnit.replace('{0}', tickets.length);
        const rows = tickets.map((t, idx) =>
            '<div class="np-confirm-row align-items-start">'
            + '<i class="bi bi-person-badge"></i>'
            + '<div class="flex-grow-1 min-w-0">'
            +   '<div class="np-confirm-val">'+esc(t.guest)+'</div>'
            +   '<div class="np-confirm-lbl mb-1">'
            +     '<span class="d-block"><i class="bi bi-geo-alt me-1"></i>'+NP_L.area+': '+esc(t.location)+'</span>'
            +     '<span class="d-block"><i class="bi bi-wifi me-1"></i>'+NP_L.lblWifi+': '+esc(t.ssid)+'</span>'
            +   '</div>'
            +   '<div class="font-mono" style="font-size:12.5px;color:var(--np-text)">Username: <b>'+esc(t.username)+'</b><br>Password: <b>'+esc(t.password)+'</b></div>'
            + '</div>'
            + '<button type="button" class="btn btn-sm btn-outline-primary np-save-img flex-shrink-0 align-self-start" data-idx="'+idx+'"><i class="bi bi-download me-1"></i>'+NP_L.saveImage+'</button>'
            + '</div>'
        ).join('');
        document.getElementById('reqResultList').innerHTML = '<div class="np-confirm-box">'+rows+'</div>';
    }
    document.getElementById('reqPrintAll').addEventListener('click', function(){ if(issuedTickets.length) printTickets(issuedTickets); });

    // บันทึก voucher เป็นรูป PNG ทีละใบ — render การ์ดตั๋วเต็มใบนอกจอแล้ว capture
    async function saveTicketImage(idx, btn){
        const t = issuedTickets[idx];
        if (!t || !window.htmlToImage) return;
        const orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        // กล่องชั่วคราวนอกจอ (vm-ticket ใช้ CSS จาก style.css ของหน้าหลัก)
        const holder = document.createElement('div');
        holder.className = 'np-capture';   // บังคับการ์ดเป็นแนวนอนเหมือนตอนพิมพ์
        holder.style.cssText = 'position:fixed;left:-99999px;top:0;width:730px;background:#fff;';
        holder.innerHTML = buildTicket(t);
        document.body.appendChild(holder);
        try {
            await document.fonts.ready;   // กันฟอนต์ไทยยังโหลดไม่เสร็จ
            const node = holder.querySelector('.vm-ticket-wrap');
            const dataUrl = await htmlToImage.toPng(node, { pixelRatio: 2, backgroundColor: '#ffffff' });
            const a = document.createElement('a');
            a.href = dataUrl;
            a.download = 'voucher-' + (t.username || 'wifi') + '.png';
            a.click();
        } catch (e) {
            alert(NP_L.genErr);
        } finally {
            holder.remove();
            btn.disabled = false;
            btn.innerHTML = orig;
        }
    }

    // ผูกปุ่ม Save แบบ delegation (ปุ่มถูกสร้างแบบ dynamic)
    document.getElementById('reqResultList').addEventListener('click', function(e){
        const btn = e.target.closest('.np-save-img');
        if (btn) saveTicketImage(parseInt(btn.dataset.idx), btn);
    });

    // reset ทุกครั้งที่ปิด/เปิด
    reqModal.addEventListener('hidden.bs.modal', resetWizard);
    function resetWizard(){
        issuedTickets=[];
        selLocId=null; selLocName=''; selDur=null; selDurLabel='';
        accept.checked=false;
        guestList.innerHTML='';
        const supInp=document.getElementById('reqSupplier');
        supInp.value=''; supInp.classList.remove('is-invalid');
        const supErr=supInp.nextElementSibling;
        if(supErr){ supErr.textContent=''; supErr.classList.remove('show'); }
        guestErr.classList.add('d-none');
        document.querySelectorAll('.np-loc-card,.np-dur-card').forEach(c=>c.classList.remove('selected'));
        showStep(1);
    }
    showStep(1);

    // ── เปิด modal อัตโนมัติจาก URL ──
    // เด้ง requestModal ถ้า URL มี #request หรือ ?open=request (reload แล้วยังค้างอยู่)
    function autoOpenFromUrl(){
        const params = new URLSearchParams(location.search);
        const wantOpen = location.hash === '#request' || params.get('open') === 'request';
        if(wantOpen) bootstrap.Modal.getOrCreateInstance(reqModal).show();
    }

    // ใส่ ?open=request ลง URL เมื่อ modal เปิด — reload แล้วยังจับได้ (ครอบทุกปุ่มที่เปิด modal)
    function setOpenInUrl(){
        const url = new URL(location.href);
        url.searchParams.set('open', 'request');
        history.replaceState(null, '', url.pathname + url.search);
    }
    reqModal.addEventListener('shown.bs.modal', setOpenInUrl);

    // ล้าง #request / ?open ออกจาก URL เมื่อปิด modal — reload ครั้งถัดไปจะไม่เด้งซ้ำ
    function clearOpenFromUrl(){
        const url = new URL(location.href);
        url.hash = '';
        url.searchParams.delete('open');
        history.replaceState(null, '', url.pathname + url.search);
    }
    reqModal.addEventListener('hidden.bs.modal', clearOpenFromUrl);

    // รันหลัง DOM + bootstrap โหลดเสร็จ (สคริปต์นี้ถูก include ก่อน bootstrap.bundle)
    if(document.readyState === 'loading'){
        document.addEventListener('DOMContentLoaded', autoOpenFromUrl);
    } else {
        autoOpenFromUrl();
    }
})();
