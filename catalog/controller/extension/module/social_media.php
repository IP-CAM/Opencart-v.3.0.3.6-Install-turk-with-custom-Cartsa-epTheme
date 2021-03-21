<?php

class ControllerExtensionModuleSocialMedia extends Controller
{
    public function index($setting)
    {
        $data['facebook'] = $setting['facebook'];
        $data['instagram'] = $setting['instagram'];
        $data['whatsapp'] = $setting['whatsapp'];
        $data['twitter'] = $setting['twitter'];
        $data['pinterest'] = $setting['pinterest'];

        return $this->load->view('extension/module/social_media', $data);
    }
}
