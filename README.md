### Komunikator PBX - Открытая и свободная АТС на базе [Yate PBX](http://www.yate.ro/products.php)

Основные функции АТС включают следующие:
- Маршрутизация и переадресация вызовов
- Автосекретарь
- Запись разговоров

#### Лицензия: 
GNU GPLv3

#### Инсталляция
[Файл-сценарий](https://raw.githubusercontent.com/komunikator/komunikator_light/master/repos/IP-PBX.sh) для автоматической установки проекта.
[Домашняя страница проекта.](https://komunikator.ru/ip_ats)

#### Для полноценной работы проекта и всех его модулей необходимы:
- nginx
- MySQL
- PHP-FPM
- PHP Pear
- sox
- madplay
- lame

Для установки проекта в консоли выполнить:
```sh
  wget https://raw.githubusercontent.com/komunikator/komunikator_light/master/repos/IP-PBX.sh  
  sudo bash ./IP-PBX.sh
````

#### Конфигурация системы
Более подробное описание АТС, а также инструкции по её настройке можно найти в [нашем блоге](https://komunikator.ru/news/index.php?tags=%D0%BD%D0%B0%D1%81%D1%82%D1%80%D0%BE%D0%B9%D0%BA%D0%B0+Komunikator)
