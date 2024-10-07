<?php
namespace App;
require_once 'Infrastructure/sdbh.php'; use sdbh\sdbh; 

class Calculate
{
    public $days;
    public $product;
    public $services;
    public $total_price;
    public $product_price;
    public $service_price;

    public function __construct($days,$product,$services)
	{
        $this->days = $days;
        $this->product = $product;
        $this->services = $services;
	}

    public function getTotalPrice() //функция вывода (итоговой суммы проката, все для tooltip: дни проката, тариф, доп.услуги)
    {
        $total = $this->total_price . " р.";
        $day = $this->days;
        $tariff = $this->product_price . " р./сутки";

        //условия для корректной формы слова 
        if ($day == 1 || $day == 21) $day .= " день";
        elseif (($day > 1 && $day <= 4) || ($day > 21 && $day <= 24)) $day .= " дня";
        elseif (($day > 4 && $day <= 20) || ($day > 24)) $day .= " дней";

        //если сумма доп.услуг не 0, то дописываем для вывода текст
        $service = $this->service_price;
        if ($service != 0) $service = "+ $service р./сутки за доп.услуги";
        else $service = "";

        $answer = array(
            "service_price" => $service,
            "tariff" => "$tariff",
            "total_price" => "$total",
            "days" => "$day"
        );

        return $answer;
    }

    public function setProductPrice() //функция установки суммы за 1 день на продукт проката исходя из тарифа
    {
        $dbh = new sdbh();
        $product = $dbh->make_query("SELECT * FROM a25_products WHERE ID = $this->product");

        if ($product) 
        {
            $this->product_price = $product[0]['PRICE'];
            $tarif = $product[0]['TARIFF'];
        } 
        else 
        {
            return false;
        }

        $tarifs = unserialize($tarif);
        if (is_array($tarifs)) 
        { 
            ksort($tarifs); 
            foreach ($tarifs as $day_count => $tarif_price) 
            {
                if ($this->days >= $day_count) 
                {
                    $this->product_price = $tarif_price;
                }
            }
        }
        return true;
    }

    public function setServicePrice() //функция установки суммы доп.услуг за 1 день проката 
    {
        if (!empty($this->services)) $this->service_price = array_sum($this->services);
        else $this->service_price = 0;
    }

    public function calculate() //функция расчета итоговой суммы проката за все дни (вместе с доп.услугами)
    {
        if (!$this->setProductPrice())
        {
            echo "Ошибка, товар не найден!";
            return;
        } 
        $this->setServicePrice();
        $this->total_price = ($this->product_price + $this->service_price) * $this->days;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') 
{
    $days = isset($_POST['days']) ? $_POST['days'] : 0;
    $product_id = isset($_POST['product']) ? $_POST['product'] : 0;
    $selected_services = isset($_POST['services']) ? $_POST['services'] : [];
    // $selected_services = array("0"=>300,"1"=>600);

    if ($days == 0 && $product_id == 0 && empty($selected_services)) echo "Ошибка, данные не переданы!";
    else 
    {
        $instance = new Calculate($days,$product_id,$selected_services);
        $instance->calculate();
        $answer = $instance->getTotalPrice();
        echo json_encode($answer);
    }
    
}
