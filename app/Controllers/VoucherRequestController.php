<?php

namespace App\Controllers;

use App\Models\LocationModel;
use App\Models\VoucherIssueModel;
use App\Models\VoucherModel;
use App\Services\ActivityLog;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * VoucherRequestController — รับคำขอและออก voucher จาก request modal (ใช้ได้ทั้ง admin และ user)
 */
class VoucherRequestController extends BaseController
{
    // ออก voucher หลายใบ: รับ location + duration + guests[] → ออกใน transaction → คืน tickets[]
    public function request(): ResponseInterface
    {
        helper('netpass');

        $locationId = (int) $this->request->getPost('location_id');
        $duration   = trim((string) $this->request->getPost('duration'));
        $supplier   = trim((string) $this->request->getPost('supplier'));   // supplier ค่าเดียวใช้กับ guest ทุกคน
        $guests     = $this->request->getPost('guests');
        $guests     = is_array($guests) ? array_values($guests) : [];

        // ตรวจข้อมูลเบื้องต้น
        if (! $locationId || ! $duration || ! array_key_exists($duration, config('Voucher')->durations)) {
            return $this->response->setJSON(['success' => false, 'message' => lang('Voucher.errInvalid')]);
        }
        if ($supplier === '') {
            return $this->response->setJSON(['success' => false, 'message' => lang('Voucher.errSupplierRequired')]);
        }
        if ($guests === []) {
            return $this->response->setJSON(['success' => false, 'message' => lang('Voucher.errInvalid')]);
        }

        // ตรวจ guest ครบทุกช่องทุกแถว (ชื่อ/นามสกุล/เบอร์)
        foreach ($guests as $i => $g) {
            $firstname = trim((string) ($g['firstname'] ?? ''));
            $lastname  = trim((string) ($g['lastname'] ?? ''));
            $phone     = trim((string) ($g['phone'] ?? ''));
            if ($firstname === '' || $lastname === '' || $phone === '') {
                return $this->response->setJSON(['success' => false, 'message' => lang('Voucher.errGuestIncomplete', [$i + 1])]);
            }
            // ตรวจรูปแบบเบอร์โทรไทย: ขึ้นต้น 0 ตามด้วยตัวเลขรวม 9–10 หลัก
            if (! preg_match('/^0\d{8,9}$/', preg_replace('/[\s-]/', '', $phone))) {
                return $this->response->setJSON(['success' => false, 'message' => lang('Voucher.errPhoneInvalid')]);
            }
        }

        // กันเบอร์โทร/ชื่อ-นามสกุลซ้ำข้ามรายการ (ป้องกัน copy-paste คนเดียวกันหลายใบ)
        $seenPhones = [];
        $seenNames  = [];
        foreach ($guests as $i => $g) {
            // เบอร์โทร — เทียบหลังตัดขีด/ช่องว่าง
            $normPhone = preg_replace('/[\s-]/', '', (string) ($g['phone'] ?? ''));
            if (isset($seenPhones[$normPhone])) {
                return $this->response->setJSON(['success' => false, 'message' => lang('Voucher.errDupPhoneRow', [$i + 1])]);
            }
            $seenPhones[$normPhone] = true;

            // ชื่อ-นามสกุล — เทียบหลังตัดช่องว่าง + ตัวพิมพ์เล็ก
            $normName = mb_strtolower(preg_replace('/\s+/', ' ', trim((string) ($g['firstname'] ?? '')) . '|' . trim((string) ($g['lastname'] ?? ''))));
            if (isset($seenNames[$normName])) {
                return $this->response->setJSON(['success' => false, 'message' => lang('Voucher.errDupNameRow', [$i + 1])]);
            }
            $seenNames[$normName] = true;
        }

        $voucherModel  = new VoucherModel();
        $issueModel    = new VoucherIssueModel();
        $locationModel = new LocationModel();

        $location = $locationModel->find($locationId);
        if (! $location) {
            return $this->response->setJSON(['success' => false, 'message' => lang('Voucher.errInvalid')]);
        }

        // เช็ค stock พอไหม
        $stock = $voucherModel->where('location_id', $locationId)->where('duration', $duration)->where('status', 'instock')->countAllResults();
        if ($stock < count($guests)) {
            return $this->response->setJSON(['success' => false, 'message' => lang('Voucher.errNoStock')]);
        }

        $hours    = duration_hours($duration);
        $userId   = auth()->user()->id;
        $isEn     = service('request')->getLocale() === 'en';
        $locName  = $isEn ? (($location['name_en'] ?? '') ?: $location['name']) : $location['name'];
        $durLabel = duration_label($duration);

        $db = db_connect();
        $db->transStart();

        $tickets = [];
        foreach ($guests as $g) {
            $voucher = $voucherModel->pickAvailable($locationId, $duration);
            if (! $voucher) {
                $db->transRollback();
                return $this->response->setJSON(['success' => false, 'message' => lang('Voucher.errNoStock')]);
            }

            $issuedAt  = date('Y-m-d H:i:s');
            $expiresAt = date('Y-m-d H:i:s', strtotime($issuedAt) + $hours * 3600);
            $code      = 'VC-' . strtoupper(substr(uniqid(), -6));
            $firstname = trim((string) $g['firstname']);
            $lastname  = trim((string) $g['lastname']);

            $voucherModel->update($voucher['id'], ['status' => 'issued']);
            $issueModel->insert([
                'code'            => $code,
                'voucher_id'      => $voucher['id'],
                'location_id'     => $locationId,
                'duration'        => $duration,
                'guest_name'      => trim($firstname . ' ' . $lastname),
                'guest_voucher'   => $voucher['vou_username'],
                'supplier'        => $supplier,
                'guest_firstname' => $firstname,
                'guest_lastname'  => $lastname,
                'guest_phone'     => trim((string) $g['phone']),
                'issued_by'       => $userId,
                'issued_at'       => $issuedAt,
                'expires_at'      => $expiresAt,
                'status'          => 'active',
            ]);

            $tickets[] = [
                'code'       => $code,
                'guest'      => trim($firstname . ' ' . $lastname),
                'username'   => $voucher['vou_username'],
                'password'   => $voucher['vou_password'],
                'location'   => $locName,
                'ssid'       => $location['ssid'],
                'duration'   => $durLabel,
                'expires_at' => $expiresAt,
            ];
        }

        $db->transComplete();
        if (! $db->transStatus()) {
            return $this->response->setJSON(['success' => false, 'message' => lang('Voucher.errGeneral')]);
        }

        // บันทึก audit: ผู้ขอ + ข้อมูล guest + รายละเอียด request
        // เก็บเป็น English คงที่ — ชื่อพื้นที่ใช้ name_en, duration ใช้ key ดิบ (ไม่ผูกกับภาษาที่แสดง)
        $logLoc = ($location['name_en'] ?? '') ?: ($location['name'] ?? '');
        ActivityLog::record('voucher.request', [
            'target_type'  => 'request',
            'target_id'    => $locationId,
            'target_label' => $logLoc,
            'details'      => [
                'location' => $logLoc,
                'ssid'     => $location['ssid'] ?? null,
                'duration' => $duration,
                'supplier' => $supplier,
                'count'    => count($tickets),
                'guests'   => array_map(static fn ($g, $t) => [
                    'name'     => trim(((string) ($g['firstname'] ?? '')) . ' ' . ((string) ($g['lastname'] ?? ''))),
                    'phone'    => trim((string) ($g['phone'] ?? '')),
                    'username' => $t['username'],
                ], $guests, $tickets),
            ],
        ]);

        return $this->response->setJSON(['success' => true, 'tickets' => $tickets]);
    }
}
