<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\CreateSimpleOrderRequest;
use App\Http\Requests\DriverInfoRequest;
use App\Http\Requests\UpdateUnitDescriptionRequest;
use App\Http\Resources\AddressFormtResource;
use App\Http\Resources\AddressListResource;
use App\Models\AddressList;
use App\Service\WialonService;
use Carbon\Carbon;
use Illuminate\Http\Request;


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

        $unitId = $request->post('unit_id');
        $date = $request->post('date');
        $route_ids = $request->post('route_ids');

        $addresList = AddressList::whereIn('id', $route_ids)->get();

        $postData = [];

        foreach ($addresList as $item) {
            $postData[] = [
                "y" => $item->longitude,
                "x" => $item->latitude,
                "tf" => Carbon::parse($date)->timestamp,
                "n" => $item->name,
                "tt" => Carbon::parse($date)->addMinutes(10)->timestamp,
                "f" => 0,
                "r" => 100,
                "p" => [
                    "ut" => 600,
                    "rep" => true,
                    "w" => 0,
                    "v" => 0,
                    "pr" => "",
                    "r" => [
                        "m" => 0,
                        "ndt" => 0,
                        "t" => 0,
                        "vt" => Carbon::parse($date)->timestamp,
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

    public function createOrderOld(CreateOrderRequest $request)
    {

        $unitId = $request->post('unit_id');


        $postData = [
            [
                "y" => 40.4042015076,
                "x" => 49.9562988281,
                "tf" => 1691060136,
                "n" => "15 DHASGDKJDASJD",
                "tt" => 1691070136,
                "f" => 0,
                "r" => 100,
                "p" => [
                    "ut" => 600,
                    "rep" => true,
                    "w" => 0,
                    "v" => 0,
                    "pr" => "",
                    "r" => [
                        "m" => 0,
                        "ndt" => 1200,
                        "t" => 4151,
                        "vt" => 1690837751,
                    ],
                    "criterions" => [
                        "max_late" => 0,
                        "use_unloading_late" => 0
                    ]
                ]
            ],
            [
                "y" => 40.4014587402,
                "x" => 49.9735832214,
                "tf" => 1691006401,
                "n" => "1 dertrd",
                "tt" => 1691092740,
                "f" => 0,
                "r" => 100,
                "p" => [
                    "ut" => 600,
                    "rep" => true,
                    "w" => 0,
                    "v" => 0,
                    "pr" => "",
                    "r" => [
                        "m" => 0,
                        "ndt" => 1200,
                        "t" => 1006,
                        "vt" => 1690838757
                    ],
                    "criterions" => [
                        "max_late" => 0,
                        "use_unloading_late" => 0
                    ]
                ]
            ],
            [
                "y" => 40.4475753,
                "x" => 49.7995535,
                "tf" => 1691006401,
                "n" => "3 test",
                "tt" => 1691092740,
                "f" => 0,
                "r" => 100,
                "p" => [
                    "ut" => 600,
                    "rep" => true,
                    "w" => 0,
                    "v" => 0,
                    "pr" => "",
                    "r" => [
                        "m" => 0,
                        "ndt" => 1200,
                        "t" => 1006,
                        "vt" => 1690838757
                    ],
                    "criterions" => [
                        "max_late" => 0,
                        "use_unloading_late" => 0
                    ]
                ]
            ],
        ];


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


        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://log.gps.az/api/route');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "data=[{\"tf\":1490086800,\"tt\":1490115600,\"x\":8.30299097061,\"y\":52.6686602788,\"p\":{\"n\":\"Customer\",\"a\":\"К\nлары Цеткин ул., Минск, Беларусь\",\"r\":{\"vt\":1490101247}},\"n\":\"Order\nname\"}]&resourceId=2035&token=2a6e9b192a9567ee39148d8ecab7dabc387468D82E17A776BEA3097CD3B207431FEC49AB&unitId=4708");

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
     * Список всех роутов (заказов) на текущий момент.
     * @lrd:end
     */
    public function getRouteList()
    {
        return file_get_contents('https://log.gps.az/api/routes?resourceId=' . $this->resourceId . '&token=' . $this->getToken());
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
}
