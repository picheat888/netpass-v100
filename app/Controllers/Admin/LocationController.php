<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\LocationModel;

/**
 * จัดการพื้นที่ (Location) — สร้าง / แก้ไข / ลบ พร้อม SSID และชื่อภาษาอังกฤษ
 */
class LocationController extends BaseController
{
    // กฎ validation + ข้อความ error (ภาษาไทย/อังกฤษตาม locale) ใช้ร่วมทั้ง create/update
    private function validationRules(): array
    {
        return [
            // EN เป็นชื่อหลัก (บังคับกรอก), TH เป็นตัวเลือก
            'name_en' => [
                'rules'  => 'required|max_length[150]',
                'errors' => [
                    'required'   => lang('Location.errNameEnRequired'),
                    'max_length' => lang('Location.errNameEnMax'),
                ],
            ],
            'name' => [
                'rules'  => 'permit_empty|max_length[150]',
                'errors' => ['max_length' => lang('Location.errNameMax')],
            ],
            'ssid' => [
                'rules'  => 'required|max_length[100]',
                'errors' => [
                    'required'   => lang('Location.errSsidRequired'),
                    'max_length' => lang('Location.errSsidMax'),
                ],
            ],
        ];
    }

    // สร้าง location ใหม่
    public function create()
    {
        $model = new LocationModel();

        $rules = $this->validationRules();

        // error แบบ inline: flash key เฉพาะหน้า + บอกว่าให้เปิด modal "add" กลับมา
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('loc_errors', $this->validator->getErrors())
                ->with('loc_form', 'add');
        }

        $model->insert([
            'name'    => $this->request->getPost('name'),
            'name_en' => $this->request->getPost('name_en'),
            'ssid'    => $this->request->getPost('ssid'),
        ]);

        return redirect()->to(site_url('admin/pool'))->with('message', lang('Location.created'));
    }

    // แก้ไข location ที่มีอยู่
    public function update(int $id)
    {
        $model = new LocationModel();
        $loc   = $model->find($id);
        if (! $loc) {
            return redirect()->to(site_url('admin/pool'))->with('error', lang('Location.notFound'));
        }

        $rules = $this->validationRules();

        // error แบบ inline: เปิด modal "edit" ของแถวนี้กลับมา (ส่ง id ไปด้วย)
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('loc_errors', $this->validator->getErrors())
                ->with('loc_form', 'edit')
                ->with('loc_edit_id', $id);
        }

        $model->update($id, [
            'name'    => $this->request->getPost('name'),
            'name_en' => $this->request->getPost('name_en'),
            'ssid'    => $this->request->getPost('ssid'),
        ]);

        return redirect()->to(site_url('admin/pool'))->with('message', lang('Location.updated'));
    }

    // ลบ location (ป้องกันลบถ้ายังมี voucher อยู่)
    public function delete(int $id)
    {
        $model = new LocationModel();
        $loc   = $model->find($id);
        if (! $loc) {
            return redirect()->to(site_url('admin/pool'))->with('error', lang('Location.notFound'));
        }

        // ตรวจว่ายังมี voucher ในคลังของ location นี้หรือไม่
        $count = db_connect()->table('vouchers')->where('location_id', $id)->countAllResults();
        if ($count > 0) {
            return redirect()->to(site_url('admin/pool'))->with('error', lang('Location.hasVouchers'));
        }

        $model->delete($id);

        return redirect()->to(site_url('admin/pool'))->with('message_danger', lang('Location.deleted'));
    }
}
