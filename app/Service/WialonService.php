<?php

namespace App\Service;


use App\Models\Address;
use App\Service\Wialon;
use Carbon\Carbon;

class WialonService
{
    public $token;
    public $request;
    public $wialon;
    public $plate;
    public $sid;
    public $reportResourceId = 600261056;


    public function __construct($token, $request)
    {
        $this->token = $token;
        $this->request = $request;

        $this->wialonLogin();
    }

    public function wialonLogin()
    {
        $this->wialon = new Wialon('http', 'go.gps.az');
        $res = $this->wialon->login(
            $this->token
        );

        try {
            $json = json_decode($res, 1);
            $this->sid = $json['eid'];
        } catch (\Exception $exception) {
            die($exception->getMessage());
        }


    }

    public function getData()
    {
        $data = $this->request;
        $unit = $this->getUnitId($data['unit']);
        if (!$unit) {
            return ["unit not found"];
        }


        $params = json_encode(array(
            "itemId" => $unit['id'],
            "timeFrom" => intval($data['from']),//Carbon::createFromFormat("Y-m-d", $data['from'])->unix(),
            "timeTo" => intval($data['to']), //Carbon::createFromFormat("Y-m-d", $data['to'])->unix(),
            "flags" => 0x0000,
            "flagsMask" => 0xFF00,
            "loadCount" => 0xffffffff,
        ), JSON_THROW_ON_ERROR);
        $this->wialon->messages_load_interval($params);
        //
        $params = json_encode(array(
            "itemId" => $unit['id'],
            "msgsSource" => "1",
            "timeFrom" => intval($data['from']),//Carbon::createFromFormat("Y-m-d", $data['from'])->unix(),
            "timeTo" => intval($data['to']), //Carbon::createFromFormat("Y-m-d", $data['to'])->unix(),
        ), JSON_THROW_ON_ERROR);
        $this->wialon->unit_get_trips($params);

        $params = [
            "reportResourceId" => $this->reportResourceId,
            "reportTemplateId" => 1,
            "reportTemplate" => null,
            "reportObjectId" => $unit['id'],
            "reportObjectSecId" => 0,
            "interval" => [
                "flags" => 16777216,
                "from" => intval($data['from']),
                "to" => intval($data['to'])
            ],
            "remoteExec" => 1
        ];

        $this->wialon->report_exec_report(
            json_encode($params, JSON_THROW_ON_ERROR)
        );

        $params = [

        ];
        $this->wialon->report_apply_report_result(
            json_encode($params, JSON_THROW_ON_ERROR)
        );

        $params = [
            "tableIndex" => 0,
            "config" => [
                "type" => "range",
                "data" => [
                    "from" => 0,
                    "to" => 9,
                    "level" => 0,
                    "unitInfo" => 1
                ]
            ]
        ];
        $mainData = $this->wialon->report_select_result_rows(
            json_encode($params, JSON_THROW_ON_ERROR)
        );

        $params = [
            "id" => $unit['id'],
            'flags' => 4294967295
        ];
        $currentKm = $this->wialon->core_search_item(
            json_encode($params, JSON_THROW_ON_ERROR)
        );

        return [
            'mainData' => $mainData,
            'currentKm' => $currentKm,
        ];

    }

    public function getRealtimeDistanceKm()
    {
        $data = $this->getData();

        $result = json_decode($data['mainData'], 1);
        $currentKm = json_decode($data['currentKm'], 1)['item']['cnm_km'];

        $formattedData = [];
        foreach ($result as $item) {
            if (!isset($item['c'])) {
                continue;
            }
            $data = $item['c'];
            $kmh = str_replace('km', '', $data[1]);
            $formattedData[] = [
                'plate' => $this->plate,
                'date' => $data[0],
                'kmh' => (int)$kmh,
                'duration' => $data[2],
                'total_time' => $data[3],
                'engine_hours' => $data[4],
                'current_km_total' => $currentKm
            ];
        }

        return $formattedData;
    }

    public function getUnitId(string $unit)
    {
        $params = array(
            'spec' => array(
                'itemsType' => 'avl_unit',
                'propName' => 'sys_name',
                'propValueMask' => '*',
                'sortType' => 'sys_name'
            ),
            'force' => 1,
            'from' => 0,
            'to' => 0,
            'flags' => 1
        );

        $data = $this->wialon->core_search_items(json_encode($params, JSON_THROW_ON_ERROR));
        $data = json_decode($data, true);
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                if ($item['nm'] == $unit) {
                    return $item;
                }
            }
        }
        return false;
    }

    public function getUnitList()
    {
        $params = array(
            'spec' => array(
                'itemsType' => 'avl_unit',
                'propName' => 'sys_name',
                'propValueMask' => '*',
                'sortType' => 'sys_name'
            ),
            'force' => 1,
            'from' => 0,
            'to' => 0,
            'flags' => 1
        );

        $data = $this->wialon->core_search_items(json_encode($params, JSON_THROW_ON_ERROR));
        $data = json_decode($data, true);
        return $data;
    }
    public function getDriverInfo($unitId)
    {
        $params = compact('unitId');

        $data = $this->wialon->resource_get_unit_drivers(json_encode($params, JSON_THROW_ON_ERROR));
        $data = json_decode($data, true);
        return $data;
    }

    public function getAddresList()
    {


        $params = [
            "params" => [
                [
                    "svc" => "resource/get_zone_data",
                    "params" => [
                        "itemId" => 2035,
                        "flags" => 0
                    ]
                ]
            ],
            "flags" => 0
        ];

        $data = $this->wialon->core_batch(json_encode($params, JSON_THROW_ON_ERROR));
        $data = json_decode($data, true);
        $addressList = [];
        foreach ($data[0] as $item) {
            $addressList[] = [
                'name' => $item['n'],
                'description' => $item['d'],
                'longitude' => $item['p'][0]['y'],
                'latitude' => $item['p'][0]['x'],
            ];
        }
        return $addressList;
    }
}
