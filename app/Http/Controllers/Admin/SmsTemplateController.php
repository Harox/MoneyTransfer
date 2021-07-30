<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use App\Http\Helpers\Common;

class SmsTemplateController extends Controller
{

    protected $helper;
    public function __construct()
    {
        $this->helper = new Common();
    }

    public function index($id)
    {
        $data['menu'] = 'sms_template';

        $data['list_menu'] = 'menu-' . $id;

        $data['tempId'] = $id;

        $data['temp_Data'] = $temp_Data = EmailTemplate::where(['temp_id' => $id, 'type' => 'sms'])->get();

        return view('admin.sms_templates.index', $data);
    }

    public function update(Request $request, $id)
    {
        $data[] = $request->en;
        $data[] = $request->ar;
        $data[] = $request->fr;
        $data[] = $request->pt;
        $data[] = $request->ru;
        $data[] = $request->es;
        $data[] = $request->tr;
        $data[] = $request->ch;

        $array = $data;

        array_unshift($array, "");

        unset($array[0]);

        for ($i = 1; $i < 9; $i++)
        {
            EmailTemplate::where([
                'temp_id'     => $id,
                'language_id' => $i,
            ])->update($array[$i]);
        }

        $this->helper->one_time_message('success', 'SMS Template Updated successfully!');
        return redirect()->intended('admin/sms-template/' . $id);
    }
}
