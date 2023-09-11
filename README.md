## Laravel-excel 一款基于xlswriter的laravel扩展包
[![Latest Stable Version](https://poser.pugx.org/mckue/laravel-excel/v/stable)](https://packagist.org/packages/mckue/laravel-excel)
[![Total Downloads](https://poser.pugx.org/mckue/laravel-excel/downloads)](https://packagist.org/packages/mckue/laravel-excel)
[![Latest Unstable Version](https://poser.pugx.org/mckue/laravel-excel/v/unstable)](https://packagist.org/packages/mckue/laravel-excel)
[![License](https://poser.pugx.org/mckue/laravel-excel/license)](https://packagist.org/packages/mckue/laravel-excel)

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
 php artisan php-ext-xlswriter:status
 ```
展示信息如下:
```
info:
+---------+---------------------------------------------+
| version | 1.0                                         |
| author  | mckue<https://github.com/mckue>             |
| docs    | https://github.com/mckue/laravel-excel      |
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

### 1.快速开始
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

🔥 在您的控制器中，您现在可以调用此导出：
```
<?php

namespace App\Http\Controllers;

use App\Exports\UsersExport;
use Mckue\Excel\Facades\Excel;

class UsersController extends Controller 
{
    public function export() 
    {
        return Excel::download(new UserExport, 'users.xlsx');
    }
}
```
最后添加一条能够访问导出的路由：
```
Route::get('users/export/', [UsersController::class, 'export']);
```
### 2.导出集合
InvoicesExport创建一个名为的新类app/Exports：

```
namespace App\Exports;

use App\Invoice;
use Mckue\Excel\Concerns\FromCollection;

class InvoicesExport implements FromCollection
{
    public function collection()
    {
        return Invoice::all();
    }
}
```

在您的控制器中，我们现在可以下载此导出：
``` 
public function export() 
{
    return Excel::download(new InvoicesExport, 'invoices.xlsx');
}
```
您可以选择传入是否输出标头和自定义响应标头：
``` 
public function export() 
{
    return Excel::download(new InvoicesExport, 'invoices.xlsx', true, ['X-Vapor-Base64-Encode' => 'True']);
}
```
或者将其存储在磁盘上（例如 s3）：
``` 
public function storeExcel() 
{
    return Excel::store(new InvoicesExport, 'invoices.xlsx', 's3');
}
```

### 3.使用自定义结构
```
namespace App\Exports;

use App\Invoice;
use Mckue\Excel\Concerns\FromCollection;

class InvoicesExport implements FromCollection
{
    public function collection()
    {
        return new Collection([
            [1, 2, 3],
            [4, 5, 6]
        ]);
    }
}
```

### 4.使用查询
``` 
namespace App\Exports;

use App\Invoice;
use Mckue\Excel\Concerns\FromQuery;
use Mckue\Excel\Concerns\Exportable;

class InvoicesExport implements FromQuery
{
    use Exportable;

    public function query()
    {
        return Invoice::query();
    }
}
```
### 5.使用迭代器
``` 
namespace App\Exports;

use App\Invoice;
use Mckue\Excel\Concerns\FromIterator;
use Mckue\Excel\Concerns\Exportable;

class InvoicesExport implements FromIterator
{
    use Exportable;

    public function iterator(): Iterator
    {
        ...
    }
}
```
在前面的示例中，我们使用Excel::download Facades来启动导出。
``` 
namespace App\Exports;

use App\Invoice;
use Mckue\Excel\Concerns\FromCollection;
use Mckue\Excel\Concerns\Exportable;

class InvoicesExport implements FromCollection
{
    use Exportable;

    public function collection()
    {
        return Invoice::all();
    }
}
```
我们现在可以下载导出而无需Facades：

``` 
return (new InvoicesExport)->download('invoices.xlsx');
```
或者将其存储在磁盘上：
``` 
return (new InvoicesExport)->store('invoices.xlsx', 's3');
```

[更多文档可参考WIKI](https://github.com/mckue/laravel-excel/wiki)

在此感谢 `xlswriter`的开发者`viest` 以及 `SpartnerNL/Laravel-Excel`的开发者。
如有什么问题可以及时反馈到github哦。
