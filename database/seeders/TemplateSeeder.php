<?php

namespace Database\Seeders;

use App\Models\SendTemplate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SendTemplate::factory()->create([
            'to' => 'kamaeff2@gmail.com',
            'subject' => "Аккаунт заблокирован",
            'template' => '<!DOCTYPE html><html lang=\"en\"><head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\"><link rel=\"preconnect\" href=\"https://fonts.googleapis.com\"><link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin><link href=\"https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap\" rel=\"stylesheet\"><title>Уведомление от сервиса рассылки</title><style>body{height:100vh;display:flex;align-items:center;justify-content:center;font-family:"Roboto",Arial,sans-serif;background-color:#f0f4f8;margin:0;padding:0;color:#333}.container{display:flex;flex-direction:column;align-items:center;max-width:600px;background:linear-gradient(145deg,#ffffff,#e6e9ef);border-radius:12px;padding:30px;box-shadow:0 4px 10px rgba(0,0,0,0.15)}h1{color:#ff6f00;font-size:24px;margin-bottom:5px;text-transform:uppercase;letter-spacing:1px}h2{color:#333;font-size:22px}p{font-size:16px;margin:0;color:#555;font-style:italic}.highlight{color:#5f2900;font-weight:bold}.footer{text-align:center;font-size:14px;color:#777;margin-top:20px}.footer span{color:#ff6f00;text-decoration:none;font-weight:bold}.separator{width:80%;height:1px;background-color:#ddd;margin:5px 0}</style></head><body><div class=\"container\"><h1>Уведомление о событии</h1><h2>Проблемы с транзитым счетом в Монете клиента</h2><div class=\"separator\"></div><p><span class=\"highlight\">ИНН:</span> {{inn}}</p><p><span class=\"highlight\">КПП:</span> {{kpp}}</p><p><span class=\"highlight\">Расчетный счет:</span> {{bank_acc}}</p><p><span class=\"highlight\">Транзитный счет:</span> {{transit}}</p><div class=\"separator\"></div><div class=\"footer\"><p>Сервис рассылки <span>1С-Рарус</span></p></div></div></body></html>'
        ]);
    }
}
