## Laravel-excel 一款基于xlswriter的laravel扩展包

xlswriter是一款高性能的php excel读写扩展，Laravel-excel基于[SpartnerNL/Laravel-Excel](https://github.com/SpartnerNL/Laravel-Excel)代码上，切换成xlswriter扩展。
如果您的项目使用的是[SpartnerNL/Laravel-Excel](https://github.com/SpartnerNL/Laravel-Excel)并且出现大数据导出性能问题，你不想修改大量的代码，那么当前的包可能会很适合你。
当然目前的包不可能百分之百兼容所有功能，目前只实现了部分基础的功能。

[Xlswriter文档](https://xlswriter-docs.viest.me/zh-cn)

#### 如果本扩展帮助到了你 欢迎star。

#### 如果本扩展有任何问题或有其他想法 欢迎提 issue与pull request。

### Laravel-excel使用教程
#### 环境要求
- `xlswriter` 1.3.7
- `PHP` >= 8.0
  安装请按照`XlsWriter`的官方文档:[安装教程](https://xlswriter-docs.viest.me/zh-cn/an-zhuang)

#### 安装
```
composer require mckue/laravel-excel
```

发布`laravel-excel.php`配置文件:
```
php artisan vendor:publish --provider="Mckue\Excel\ExcelServiceProvider" --tag=config
 ```
#### 1.命令
##### 1.1 查看xlswriter扩展是否正常安装
```
 php artisan xls:status
 ```
展示信息如下:
```
laravel-xlsWriter info:
+---------+---------------------------------------------+
| version | 1.0                                         |
| author  | lysice<https://github.com/Lysice>           |
| docs    | https://github.com/Lysice/laravel-xlswriter |
+---------+---------------------------------------------+
XlsWriter extension status:
+-------------------------------+----------------------------+
| loaded                        | yes                        |
| xlsWriter author              | Jiexing.Wang (wjx@php.net) |
| xlswriter support             | enabled                    |
| Version                       | 1.3.7                      |
| bundled libxlsxwriter version | 1.0.0                      |
| bundled libxlsxio version     | 0.2.27                     |
+-------------------------------+----------------------------+

```
如您的信息展示如上所示，证明您的`cli`环境下本扩展可用。

#### 1.导出array
```
<?php

namespace App\Exports;

use Mckue\Excel\Concerns\FromArray;

class UserExport implements FromArray
{
 /** 
  * @return array */ 
  public function array() : array 
  { 
    return [ ['哈哈', 'aaa'],
         ['哈哈', 'aaa'],
         ['哈哈', 'aaa'],
         ['哈哈', 'aaa']
         ]; 
  }
 /** 
   * @return array 
   */ 
 public function headers() : array {
     return []; 
 }
}
```

在此感谢 `xlswriter`的开发者`viest`。
如有什么问题可以及时反馈到github哦。
