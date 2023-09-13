<?php

namespace App\Service;


use App\Models\Address;
use App\Models\AddressList;
use App\Service\Wialon;
use Carbon\Carbon;

class WialonService
{
    public $token = '2a6e9b192a9567ee39148d8ecab7dabcD897FE5D186814C6F208B90200403AE218B2E261';
    public $request;
    public $wialon;
    public $plate;
    public $sid;
    public $reportResourceId = 600261056;


    public function __construct($request)
    {
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
                'data' => $item
            ];
        }
        return $addressList;
    }

    public function updateAddressList()
    {
        $result = $this->getAddresList();

        foreach ($result as $item) {

            AddressList::updateOrCreate([
                'name' => $item['name'],
            ], [
                'name' => $item['name'],
                'description' => $item['description'],
                'guid' => $this->getUIDFromDescription($item['description']),
                'longitude' => $item['longitude'],
                'latitude' => $item['latitude'],
                'data' => json_encode($item['data']),
            ]);
        }
        return [
            'status' => true,
            'message' => 'Updated',
        ];
    }


    public function getUIDFromDescription($text)
    {
        // if it has [#ID] in description
        if (preg_match('/\[#\w+]/', $text, $matches)) {
            // remove [# and ]
            $matches[0] = str_replace('[#', '', $matches[0]);
            $matches[0] = str_replace(']', '', $matches[0]);
            return $matches[0];
        }
    }

    public function updateUnitDescription($address_id, $guid)
    {
        $address = AddressList::where('id', $address_id)->first();
        $data = json_decode($address->data, 1);

        $requestData = $data;

        // remove [#ID] from description
        $description = $data['d'];
        $description = preg_replace('/\[#\w+]/', "[#$guid]", $description);
        // if it has [#ID] twice in description remove one
        if (substr_count($description, "[#") > 1) {
            // count for
            for ($i = 0; $i < substr_count($description, "[#") - 1; $i++) {
                $description = preg_replace('/\[#\w+]/', "", $description, 1);
            }

        }
        // trim
        $description = trim($description);
        if ($description == $data['d']) {
            $description = $data['d'] . " [#$guid]";
        }


        $requestData['d'] = $description;
        $requestData['callMode'] = "update";
        $requestData['id'] = $address_id;
        $requestData['itemId'] = 2035;


        $ch = curl_init();
        $preData = 'params=' . json_encode($requestData, 1) . '&sid=' . $this->sid;

        curl_setopt($ch, CURLOPT_URL, 'https://go.gps.az/wialon/ajax.html?svc=resource/update_zone&sid=' . $this->sid);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $preData);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

        $headers = array();
        $headers[] = 'Authority: go.gps.az';
        $headers[] = 'Accept: */*';
        $headers[] = 'Accept-Language: ru-RU,ru;q=0.9,en-GB;q=0.8,en;q=0.7,en-US;q=0.6,es;q=0.5,cs;q=0.4,de;q=0.3,pt;q=0.2,und;q=0.1,tr;q=0.1,sk;q=0.1,fr;q=0.1,it;q=0.1,az;q=0.1,hi;q=0.1';
        $headers[] = 'Cache-Control: no-cache';
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        $headers[] = 'Cookie: gr=1; lang=en; _ga=GA1.1.303240973.1692343817; _gcl_au=1.1.1035102894.1692343817; _fbp=fb.1.1692343819389.342289633; cf_clearance=wvNVfLwCh.aVdx9RF58oZp7l_3uTD98UPvR4ASOGGr8-1692347199-0-1-9167b96d.289cb557.964b89bf-0.2.1692347199; _ga_PQV3EWLJ7X=GS1.1.1692960119.7.0.1692960129.0.0.0; _ga_L6NP7TDMF6=GS1.1.1693205461.20.0.1693205466.0.0.0; sessions=a1c8a6b5b3e164ebec57d868f7727670';
        $headers[] = 'Origin: https://go.gps.az';
        $headers[] = 'Pragma: no-cache';
        $headers[] = 'Referer: https://go.gps.az/wialon/post.html';
        $headers[] = 'Sec-Ch-Ua: \"Chromium\";v=\"116\", \"Not)A;Brand\";v=\"24\", \"Google Chrome\";v=\"116\"';
        $headers[] = 'Sec-Ch-Ua-Mobile: ?0';
        $headers[] = 'Sec-Ch-Ua-Platform: \"Windows\"';
        $headers[] = 'Sec-Fetch-Dest: empty';
        $headers[] = 'Sec-Fetch-Mode: cors';
        $headers[] = 'Sec-Fetch-Site: same-origin';
        $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        $this->updateAddressList();
        return $result;
    }

    public function getReport($dateFrom,$dateTo,$unit_id)
    {


        $params = [
            //{"reportResourceId":2035,"reportTemplateId":3,"reportTemplate":null,"reportObjectId":2032,"reportObjectSecId":0,"interval":{"flags":16777216,"from":1693771200,"to":1693943999},"remoteExec":1,"reportObjectIdList":[]}
            "reportResourceId" => 2035,
            "reportTemplateId" => 3,
            "reportTemplate" => null,
            "reportObjectId" => 2032,
            "reportObjectSecId" => 0,
            "interval" => [
                "flags" => 16777216,
                "from" => $dateFrom,
                "to" => $dateTo
            ],
            "remoteExec" => 1,
            "reportObjectIdList" => []
        ];
        // report_exec_report
        $data = $this->wialon->report_exec_report(
            json_encode($params, JSON_THROW_ON_ERROR)
        );

        $params = [];
        $status = $this->wialon->report_get_report_status(
            json_encode($params, JSON_THROW_ON_ERROR)
        );

        $firstData = '';
        for ($i = 0; $i < 20; $i++) {
            $status = $this->wialon->report_get_report_status(
                json_encode($params, JSON_THROW_ON_ERROR)
            );
            $status_id = json_decode($status, 1)['status'];
            if($status_id == 4){
                $result = $this->wialon->report_apply_report_result(
                    json_encode($params, JSON_THROW_ON_ERROR)
                );
                $firstData = json_decode($result,1);
            }
            sleep(1);
        }

        $params = [
            "tableIndex" => 3, //testiq
            "config" => [
                "type" => "range",
                "data" => [
                    "from" => 0,
                    "to" => 1000,
                    "level" => 0,
                    "unitInfo" => 1
                ]
            ]
        ];
        $data = $this->wialon->report_select_result_rows(
            json_encode($params, JSON_THROW_ON_ERROR)
        );
        $full_data = json_decode($data,1);

        if($unit_id){
            foreach ($full_data as $data){
                if($data['uid'] == $unit_id){
                    return $data;
                }
            }
        }
        return [
            'data' => $firstData,
            'full_data' => $full_data
        ];
    }
}
