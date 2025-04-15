<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\CreateSimpleOrderRequest;
use App\Http\Requests\DriverInfoRequest;
use App\Http\Requests\UpdateUnitDescriptionRequest;

use App\Http\Resources\AddressListResource;
use App\Models\AddressList;
use App\Service\WialonService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class WialonController extends Controller
{
    protected $token = '2a6e9b192a9567ee39148d8ecab7dabcD897FE5D186814C6F208B90200403AE218B2E261';
    public $resourceId = 2035;

    public function getToken()
    {
        return $this->token;
    }

    public $apiUrl = 'https://log.gps.az/api';

    /**
     * @lrd:start
     * Добавление роута (заказа)
     * @lrd:end
     * @LRDparam unit_id|integer
     * @LRDparam route_ids|array
     */
    public function createOrder(CreateSimpleOrderRequest $request)
    {

        if ($this->checkOrderExists($request)) {
            return $this->addOrderToExistsOrder($request);
        }

        $unitId = $request->post('unit_id');
        $date = $request->post('date');
        $route_ids = $request->post('route_ids');

        $addressLists = AddressList::whereIn('id', $route_ids)->get();

        $postData = [];

        foreach ($addressLists as $item) {
            $postData[] = [
                "y" => $item->longitude,
                "x" => $item->latitude,
                "tf" => Carbon::parse($date)
                    ->timestamp,
                "n" => $item->name,
                "tt" => Carbon::parse($date)
                    ->setHour(
                    // set current hour
                        Carbon::now()->hour
                    )
                    ->setMinute(
                    // set current minute
                        Carbon::now()->minute
                    )
                    ->addMinutes(10)->timestamp,
                "f" => 0,
                "r" => 100,
                "p" => [
                    "ut" => 600,
                    "rep" => true,
                    "w" => 0,
                    "v" => 0,
                    "r" => [
                        "m" => 0,
                        "ndt" => 0,
                        "t" => 0,
                        "vt" => Carbon::parse($date)
                            ->setHour(
                                24 - Carbon::now()->hour
                            )
                            ->setMinute(
                                60 - Carbon::now()->minute
                            )
                            ->timestamp,
                    ],
                    "criterions" => [
                        "max_late" => 0,
                        "use_unloading_late" => 0
                    ]
                ]
            ];
        }


        $data = json_encode($postData, JSON_THROW_ON_ERROR);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->apiUrl . '/route');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "data=$data&resourceId={$this->resourceId}&token={$this->getToken()}&unitId=" . $unitId);

        $headers = array();
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        return $result;
    }

    public function addOrderToExistsOrder(CreateSimpleOrderRequest $request)
    {

        $postData = $this->checkOrderExists($request);

        $unitId = $request->post('unit_id');
        $date = $request->post('date');
        $route_ids = $request->post('route_ids');

        $addresList = AddressList::whereIn('id', $route_ids)->get();

        foreach ($addresList as $item) {
            $itemsToAdd = [
//        $postData['orders'][] = [
                "uid" => 0,
                "id" => 0,
                'cnm' => 0,
                "y" => $item->longitude,
                "x" => $item->latitude,
                "tf" => Carbon::parse($date)->timestamp,
                "n" => $item->name,
                "tt" => Carbon::parse($date)->addMinutes(10)->timestamp,
                "f" => 0,
                "r" => 100,
                "u" => $unitId,
                "p" => [
                    "ut" => 600,
                    "rep" => true,
                    "w" => 0,
                    "v" => 0,
                    "r" => [
                        "id" => $postData['uid'],
                        "m" => 0,
                        "ndt" => 0,
                        "t" => 0,
                        "vt" => Carbon::parse($date)
                            ->setHour(
                                24 - Carbon::now()->hour
                            )
                            ->setMinute(
                               60 - Carbon::now()->minute
                            )
                            ->timestamp,
                    ],
                    "criterions" => [
                        "max_late" => 0,
                        "use_unloading_late" => 0
                    ]
                ],
                "callMode" => "create",
            ];
            array_unshift($postData['orders'], $itemsToAdd);
        }


        // revers orders

        $params = json_encode($postData, JSON_THROW_ON_ERROR);


        $wialon = new WialonService($request);
//        return $params;
        $token = $wialon->sid;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://go.gps.az/wialon/ajax.html?svc=order/route_update&sid=' . $token);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'authority: go.gps.az',
            'accept: */*',
            'accept-language: ru-RU,ru;q=0.9,en-GB;q=0.8,en;q=0.7,en-US;q=0.6,es;q=0.5,cs;q=0.4,de;q=0.3,pt;q=0.2,und;q=0.1,tr;q=0.1,sk;q=0.1,fr;q=0.1,it;q=0.1,az;q=0.1,hi;q=0.1',
            'content-type: application/x-www-form-urlencoded',
            'origin: https://go.gps.az',
            'referer: https://go.gps.az/wialon/post.html',
            'sec-ch-ua: "Google Chrome";v="117", "Not;A=Brand";v="8", "Chromium";v="117"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Windows"',
            'sec-fetch-dest: empty',
            'sec-fetch-mode: cors',
            'sec-fetch-site: same-origin',
            'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36',
            'accept-encoding: gzip',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'params=' . $params . '&sid=' . $token);

        $result = curl_exec($ch);

        curl_close($ch);
        return [
            'status' => 'success',
            'message' => 'Order added to exists order'
        ];
    }

    /**
     * @lrd:start
     * Список всех машин
     * @lrd:end
     */
    public function getUnitList(Request $request)
    {
        $wialonService = new WialonService($request);

        return $wialonService->getUnitList();
    }

    /**
     * @lrd:start
     * Информация о машине
     * @lrd:end
     * @LRDparam unit_id|integer
     */
    public function getDriverInfo(DriverInfoRequest $request)
    {

        $unitId = $request->post('unit_id');
        $wialonService = new WialonService($request);

        return $wialonService->getDriverInfo($unitId);
    }
    /**
     * @lrd:start
     * Список всех водителей
     * @lrd:end
     */
    public function getDriverList(Request $request)
    {

        $wialonService = new WialonService($request);

        return $wialonService->getDriverList();
    }


    /**
     * @lrd:start
     * Список всех роутов (заказов) на текущий момент.
     * @lrd:end
     * @LRDparam unit_id
     * @LRDparam from|date d-m-Y
     * @LRDparam to|date d-m-Y
     */
    public function getRouteList(Request $request)
    {
        $url = 'https://log.gps.az/api/routes?resourceId=' . $this->resourceId . '&token=' . $this->getToken();
        return file_get_contents($url);
    }

    public function getUnitRouteList($from, $to, $unit_id)
    {

        $dateFrom = Carbon::parse($from)
            ->setSecond(0)
            ->setMinute(0)
            ->setHour(0)
            ->timestamp;
        $dateTo = Carbon::parse($to)
            ->setSecond(59)
            ->setMinute(59)
            ->setHour(23)
            ->timestamp;

        $url = 'https://log.gps.az/api/routes?unitIds=' . $unit_id . '&from=' . $dateFrom . '&to=' . $dateTo . '&resourceId=' . $this->resourceId . '&token=' . $this->getToken();

        return file_get_contents($url);
    }

    public function checkOrderExists(CreateSimpleOrderRequest $request)
    {
        $unitId = $request->post('unit_id');
        $date = $request->post('date');

        $unitOrder = $this->getUnitRouteList($date, $date, $unitId);
        $result = json_decode($unitOrder, true);

        $orders = [];
        if (isset($result['routes'][0]['orders'])) {
            $order = $result['routes'][0];
            $orderId = $order['uid'];
            $plate = $order['n'];

            foreach ($order['orders'] as $item) {
                $orders[] = [
                    'id' => $item['id'],
                ];
            }
        } else {
            return null;
        }
        return [
            'itemId' => $this->resourceId,
            'orders' => $orders,
            'uid' => $orderId,
            'callMode' => 'update',
            'exp' => 0,
            'f' => 0,
            'n' => $plate
        ];

    }

    /**
     * @lrd:start
     * Список всех пунктов из Wialon Geofences
     * @lrd:end
     */
    public function getAddressList(Request $request)
    {
        return AddressListResource::collection(AddressList::all())->resolve();
    }


    /**
     * @lrd:start
     * Обновление guid в описание геозоны в Wialon Geofences
     * @lrd:end
     */
    public function updateUnitDescription(UpdateUnitDescriptionRequest $request)
    {
        $address_id = $request->post('address_id');
        $guid = $request->post('guid');
        $wialonService = new WialonService($request);
        return $wialonService->updateUnitDescription($address_id, $guid);
    }


    /**
     * @param Request $request
     * @return array
     * @throws \JsonException
     * @LRDparam from|date d-m-Y
     * @LRDparam to|date d-m-Y
     */
    public function getReport(Request $request)
    {
        $dateFrom = $request->get('from');
        $dateTo = $request->get('to');
        $unit_id = $request->get('unit_id') ?? null;
        // validate
        $request->validate([
            'from' => 'required|date_format:d-m-Y',
            'to' => 'required|date_format:d-m-Y',
        ]);

        $dateFrom = Carbon::parse($dateFrom)
            ->setSecond(0)
            ->setMinute(0)
            ->setHour(0)
            ->timestamp;
        $dateTo = Carbon::parse($dateTo)
            ->setSecond(59)
            ->setMinute(59)
            ->setHour(23)
            ->timestamp;
        // if diff between dates more than 1 month
        if (Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo)) > 3) {
            return [
                'status' => 'error',
                'message' => 'diff between dates more than 3 days'
            ];
        }

        $wialonService = new WialonService($request);
        return $wialonService->getReport(
            $dateFrom,
            $dateTo,
            $unit_id
        );
    }
}
