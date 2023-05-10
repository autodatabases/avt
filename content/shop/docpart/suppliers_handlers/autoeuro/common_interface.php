<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");
// подключаем класс клиента
include('ae_client/cli_main.php');
class autoeuro_enclosure
{
	public $result;
	public $Products = array();//Список товаров
	public function __construct($article, $storage_options)
	{
		$config = array (
			'server' => 'http://online.autoeuro.ru/ae_server/srv_main.php',
			'client_name' => $storage_options["client_name"],
			'client_pwd' => $storage_options["client_pwd"],
		);
		// создаем экземпляр класса
	    $aeClient = new AutoeuroClient($config);
	    $data1 = $aeClient->getData( 'Search_By_Code', array($article,1) );//Выполняем процедуру получения товаров по артикулу
		//Формируем массив брэндов:
        $brands_array = array();
        foreach ($data1 as &$value) {
            array_push($brands_array, $value["maker"]);
        }
	    //ТЕПЕРЬ ПОЛУЧАЕМ СПИСОК ТОВАРОВ ПО ВСЕМ БРЭНДАМ:
        
        {
			// вызов процедуры 'Get_Element_Details' с 3-мя параметрами: 'RUV',5413,1
			$data2 = $aeClient->getData( 'Get_Element_Details', array($brands_array[$i],$article,1) );
            foreach($data2 as &$value)
            {
                $value["name"] = iconv ( "cp1251" , "utf8" , $value["name"] );
                $time = explode("-", $value["order_time"]);
                $price = $value["price"];
                //Наценка
    		    $markup = $storage_options["markups"][(int)$price];
    		    if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
    		    {
    		        $markup = $storage_options["markups"][count($storage_options["markups"])-1];
    		    }
				//Минимальный заказ:
				$min_order = (int)$value['packing'];
				if( $min_order < 1 )
				{
					$min_order = 1;
				}
				
                //Создаем объек товара и добавляем его в список:
    			$DocpartProduct = new DocpartProduct($value["maker"],
                    $value["code"],
                    $value["name"],
                    $value["amount"],
                    $price + $price*$markup,
                    $time[0] + $storage_options["additional_time"],
                    $time[1] + $storage_options["additional_time"],
                    NULL,
                    $min_order,
                    $storage_options["probability"],
                    $storage_options["office_id"],
                    $storage_options["storage_id"],
                    $storage_options["office_caption"],
                    $storage_options["color"],
                    $storage_options["storage_caption"],
                    $price,
                    $markup,
                    2,0,0,'',null,array("rate"=>$storage_options["rate"])
                    );
                if($DocpartProduct->valid == true)
				{
					array_push($this->Products, $DocpartProduct);
				}
            }
        }//~for $brands_array
		$this->result = 1;
	}//~function __construct($article)
};//~class autoeuro_enclosure

$ob = new autoeuro_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>