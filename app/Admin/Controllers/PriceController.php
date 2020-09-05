<?php

namespace App\Admin\Controllers;

use App\Models\Price;
use App\Models\City;
use App\Models\Waste;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Illuminate\Http\Request;

class PriceController extends Controller
{
    use ModelForm;

    /**
     * Index interface.
     *
     * @return Content
     */
    public function index()
    {
        if (!Admin::user()->isRole('administrator')) {
            return redirect (route('index'));
        }

        Admin::script($this->toolGroupPrice());
        return Admin::content(function (Content $content) {
            $content->header('Цены');
            $content->description('Список');
            $content->body($this->grid());
        });
    }

    /**
     * Edit interface.
     *
     * @param $id
     * @return Content
     */
    public function edit($id)
    {
        if (!Admin::user()->isRole('administrator')) {
            return redirect (route('index'));
        }

        return Admin::content(function (Content $content) use ($id) {
            $content->header('Цены');
            $content->description('Редактировать');
            $content->body($this->form()->edit($id));
        });
    }

    /**
     * Create interface.
     *
     * @return Content
     */
    public function create()
    {
        if (!Admin::user()->isRole('administrator')) {
            return redirect (route('index'));
        }

        return Admin::content(function (Content $content) {
            $content->header('Цены');
            $content->description('Создание');
            $content->body($this->form());
        });
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Admin::grid(Price::class, function (Grid $grid) {
            $grid->disableExport();
            $grid->disableRowSelector();
            $grid->tools(function($tools) {
                $tools->disableRefreshButton();
                // добавим заголовок для грида
                $tools->append('<input type="text" id="group_price" placeholder="Цена"><button id="btnGroupPrice">Применить</button>');
            });

            $grid->price_id('ID')->sortable();
            $grid->city()->name('Город')->sortable();
            $grid->waste()->name('Втор сырье')->sortable();
            $grid->price('Цена')->sortable();

            $grid->filter(function ($filter) {
                $filter->disableIdFilter();
                $filter->is('city_id', 'Город')->select(City::all()->pluck('name', 'city_id'));
                $filter->is('waste_id', 'Втор сырье')->select(Waste::all()->pluck('name', 'waste_id'));
            });
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Admin::form(Price::class, function (Form $form) {
            $form->select('city_id', 'Город')->options(City::all()->pluck('name', 'city_id'));
            $form->select('waste_id', 'Втор сырье')->options(Waste::all()->pluck('name', 'waste_id'));
            $form->currency('price', 'Цена')->symbol('RUB');
        });
    }

    private function toolGroupPrice()
    {
        return <<<SCRIPT

$('#btnGroupPrice').click(function() {
    var price = $('#group_price').val();
    var city = $('.city_id').val();
    var waste = $('.waste_id').val();
    
    if (price == '') {
        alert('Заполните поле прайса');
        return false;
    }

    if (city == '' && waste == '') {
        if (!confirm('Назначить одну цену всем?')) {
            return false;
        }
    }

    $.ajax({
        method: 'post',
        url: '/price/group-edit',
        data: {
            _token:LA.token,
            price: price,
            city: city,
            waste: waste
        },
        success: function (data) {
            if (typeof data === 'object') {
                if (data.status) {
                    toastr.success(data.message);
                } else {
                    toastr.error(data.message);
                }
            }

            $.pjax.reload('#pjax-container');
        }
    });
});
SCRIPT;
    }

    public function groupEdit(Request $request)
    {
        if (!Admin::user()->isRole('administrator')) {
            return redirect (route('index'));
        }

        $where = [];
        $price = (float) $request->input('price');
        $city = $request->input('city');
        $waste = $request->input('waste');

        if (empty($price)) {
            return json_encode(['status' => 0, 'message' => 'Цена не может быть пустой']);
        }
        if (!empty($city)) {
            $cityDb = City::where('city_id', $city)->get();
        } else {
            $cityDb = City::all();
        }

        if (!empty($waste)) {
            $wasteDb = Waste::where('waste_id', $waste)->get();
        } else {
            $wasteDb = Waste::all();
        }

        foreach ($cityDb as $cityData) {
            foreach ($wasteDb as $wasteData) {
                $data = [
                    'city_id' => $cityData->city_id, 
                    'waste_id' => $wasteData->waste_id,
                ];
                $priceDb = Price::where($data)->first();
                
                if (empty($priceDb)) {
                    $data['price'] = $price;
                    Price::create($data);
                } else {
                    $priceDb->price = $price;
                    $priceDb->save();
                }
            }
        }

        return json_encode(['status' => 1, 'message' => 'Цены обновлены']);
    }
}
