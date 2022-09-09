<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ControllerGenerateConfig extends Controller
{

    public function download () {
        return Storage::download('config.txt');
    }

    public function generateConfig ()
    {   

        $id = request('id');

        $arrJson = Http::pool(fn (Pool $pool) => [
            $pool->as('global')->get('http://confdata2.proxicom.ru/global.json'),
            $pool->as('model')->get('http://confdata2.proxicom.ru/reqs/' . $id . '.json')
        ]);

        $config = [];
        $interfacesVlan = [];

        $objGlobal = $arrJson['global']->json();
        $objModel = $arrJson['model']->json();

        if ($objModel === null || $objGlobal === null) return view( 'welcome', ['config' => $config = ['+++']] );

        try {
            $interfacesVlan = ControllerGenerateConfig::getInterfacesVlan($objGlobal, $id);
            $interfacesIsolatePort = ControllerGenerateConfig::getIsolatePort($objGlobal, $id);
            $interfaceEthernet = ControllerGenerateConfig::getInterfaceEthernet($objGlobal, $objModel, $id);

            $config = [...$interfaceEthernet, ...$interfacesVlan, ...$interfacesIsolatePort];

        } catch (Exception $e) {
            $config = ['+++'];
        }

        Storage::put('config.txt', '!');

        for ($i = 0; $i < count($config); $i++) Storage::append('config.txt', $config[$i]);

        return view( 'welcome', ['config' => $config] );
    }

    public function getInterfaceEthernet ($objGlobal, $objModel, $id) {
        $arrInterfaceEthernet = [];
        $modelSw = $objGlobal['SwitchList']['SwId-' . $id]['SwitchModel'];
        $isolateGroup = $objGlobal['models'][$modelSw]['PortList'];
        $specialPorts = $objModel['SpecialPorts'];

        for ($i = 1; $i < count($isolateGroup) + 1 ; $i++) {
            if ($i < 25 ) array_push( $arrInterfaceEthernet, ...ControllerGenerateConfig::getGenerateEthernet ($specialPorts, $i) );
            else array_push( $arrInterfaceEthernet, ...ControllerGenerateConfig::getGenerateEthernetTrunk ($specialPorts, $i) );
        }

        return $arrInterfaceEthernet;
    }

    public function getGenerateEthernet ($specialPorts, $i) {
        $accessVlan = '860';
        if ( $specialPorts['Ethernet1/' . $i]['AccessVlan'] ?? '' ) $accessVlan = $specialPorts['Ethernet1/' . $i]['AccessVlan'];
 
        return [
            'Interface Ethernet1/' . $i,
            ' ' . 'storm-control broadcast 63',
            ' ' . 'storm-control multicast 63',
            ' ' . 'storm-control unicast 63',
            ' ' . 'transceiver-monitoring enable',
            ' ' . 'lldp disable',
            ' ' . 'no spanning-tree',
            ' ' . 'switchport access vlan ' . $accessVlan, // По условию ???
            ' ' . 'loopback-detection control shutdown',
            '!'
        ];
    }

    public function getGenerateEthernetTrunk ($specialPorts, $i) { // ???
        $str = '';
        if ( $specialPorts['Ethernet1/' . $i]['TrunkVlans'] ?? '' ) $str = implode(",", $specialPorts['Ethernet1/' . $i]['TrunkVlans']);
 
        return [
            'Interface Ethernet1/' . $i,
                ' ' . 'no spanning-tree',
                ' ' . 'switchport mode trunk',
                ' ' . 'switchport trunk allowed vlan ' . $str,
                ' ' . 'switchport trunk allowed vlan add',
                ' ' . 'switchport trunk native vlan',
                '!'
        ];
    }

    public function getIsolatePort ($objGlobal, $id) {
        $arrIsolatePort = [];
        $modelSw = $objGlobal['SwitchList']['SwId-' . $id]['SwitchModel']; // Дубль вынесть
        $isolateGroup = $objGlobal['models'][$modelSw]['CustomerIsolateGroup']; // key ???

        for ($i = 24; $i >= 1; $i--) array_push( $arrIsolatePort, 'isolate-port group' . $isolateGroup . 'switchport interface Ethernet1/' . $i );

        return $arrIsolatePort;
    }

    public function getInterfacesVlan ($objGlobal, $id)
    {
        $objControlVlans = $objGlobal['SwitchList']['SwId-' . $id]; // key ???

        $interfaceVlans = $objControlVlans['ControlVlans'];
        $ipDefaultGateway = $objControlVlans['IpDefaultGateway'];
        $defaultCustomerVlan = $objControlVlans['DefaultCustomerVlan'];

        $arrInterfaceVlans = [];
        foreach ($interfaceVlans as $key => $value)
            array_push( $arrInterfaceVlans, ...ControllerGenerateConfig::getIpAndMask ($key, $value) );

        array_push( $arrInterfaceVlans, ...['mac-address-table notification', '!'] );
        array_push( $arrInterfaceVlans, ...['ip default-gateway ' . $ipDefaultGateway, '!'] );

        return $arrInterfaceVlans;
    }

    public function getIpAndMask ($vlan, $ip) {
        $listInterfaceVlan = [ // вынести
            'interfaceVlan' => 'interface Vlan',
            'ip' => 'ip address'
        ];

        $ip_with_mask = $ip['ip']; // ???
        list($ip, $mask_int) = explode('/', $ip_with_mask);
        $mask_nr = (pow(2, $mask_int) - 1) << (32 - $mask_int); 
        $mask = long2ip($mask_nr);
        $subnet_ip = long2ip(ip2long($ip) & $mask_nr);
        $gateway_ip = long2ip((ip2long($ip) & $mask_nr) + 1);

        return [
            $listInterfaceVlan['interfaceVlan'] . $vlan,
            ' ' . $listInterfaceVlan['ip'] . ' ' . $ip . ' ' . $mask,
            '!'
        ];

    }
}
