<?php

namespace App\Admin\Controllers;

use App\Models\DeliveryWaste;
use App\Models\Waste;
use App\Models\City;
use App\Models\Price;
use App\Models\Point;
use App\User;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Illuminate\Http\Request;

class DeliveryWasteController extends Controller
{
    use ModelForm;

    /**
     * Index interface.
     *
     * @return Content
     */
    public function index()
    {
        return Admin::content(function (Content $content) {
            $content->header('Сдача вторсырья');
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
        return Admin::content(function (Content $content) use ($id) {
            $content->header('Сдача вторсырья');
            $content->description('Редактирование');
            $content->body($this->form(true)->edit($id));
        });
    }

    /**
     * Create interface.
     *
     * @return Content
     */
    public function create()
    {
        return Admin::content(function (Content $content) {
            $content->header('Сдача вторсырья');
            $content->description('Создание');
            $content->body($this->form(false));
        });
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Admin::grid(DeliveryWaste::class, function (Grid $grid) {
            $grid->disableRowSelector();
            $grid->disableExport();

            if (Admin::user()->isRole('user')) {
                $grid->model()->where('user_id', Admin::user()->id);
                $grid->actions(function (Grid\Displayers\Actions $actions) {
                    if ($actions->row->paid) {
                        $actions->disableDelete();
                        $actions->disableEdit();
                    }
                });
            }

            $grid->delivery_waste_id('ID')->sortable();

            if (Admin::user()->isRole('administrator')) {
                $grid->user_id('Пользователь')->display(function($userId) {
                    $user = User::find($userId);
                    $userName = '<span style="color: red;">Удаленный</span>';
                    if (!empty($user)) {
                        $userName = $user->name;
                    }
                    return $userName;
                })->sortable();
                
                $grid->actions(function ($actions) {
                    $actions->disableDelete();
                    if (!$actions->row->paid) {
                        $actions->append('<a href="' . route('paid', $actions->row->delivery_waste_id) . '" title="Оплачен"><i class="fa fa-credit-card"></i></a>');
                    }
                });
            }

            $grid->column('Город')->display(function(){
                $cityName = '<span style="color: red;">Удаленный</span>';

                $point = Point::where('point_id', $this->point_id)->first();
                if (empty($point->city_id)) {
                    return $cityName;
                }
                
                $city = City::where('city_id', $point->city_id)->first();
                if (!empty($city)) {
                    $cityName = $city->name;
                }
                return $cityName;
            })->sortable();
            $grid->column('Пункт приема')->display(function(){
                $point = Point::where('point_id', $this->point_id)->first();
                $pointName = '<span style="color: red;">Удаленный</span>';
                if (!empty($point)) {
                        $pointName = $point->name . ' - ' . $point->address;
                    }
                return $pointName;
            })->sortable();

            $grid->waste_id('Вторсырье')->display(function($wasteId) {
                $waste = Waste::find($wasteId);
                $wasteName = '<span style="color: red;">Удаленный</span>';
                if (!empty($waste)) {
                        $wasteName = $waste->name;
                    }
                return $wasteName;
            })->sortable();
            $grid->bulk('Объем (кг)')->sortable()->totalRow();
            $grid->price('Цена')->sortable();
            $grid->summa('Сумма')->sortable()->totalRow();
            $grid->paid('Оплата')->display(function ($released) {
                    return $released ? '<span class="label-success label">Да</span>' : '<span class="label-default label">Нет</span>';
                })->sortable();
            $grid->date_delivery('Дата сдачи')->display(function ($date) {
                return date('d.m.Y H:i', strtotime($date));
            })->sortable();
            $grid->date_paid('Дата оплаты')->display(function ($date) {
                $newDate = '';
                if (!empty($date)) {
                    $newDate = date('d.m.Y H:i', strtotime($date));
                } 
                return $newDate;
            })->sortable();
            $grid->created_at('Создан')->display(function ($date) {
                return date('d.m.Y H:i', strtotime($date));
            })->sortable();
            $grid->updated_at('Отредактирован')->display(function ($date) {
                return date('d.m.Y H:i', strtotime($date));
            })->sortable();

            $grid->filter(function ($filter) {
                $filter->useModal();
                $filter->disableIdFilter();

                if (Admin::user()->isRole('administrator')) {
                    $filter->where(function ($query) {
                        $query->whereIn('user_id', User::where('name', 'like', '%' . $this->input . '%')->get()->pluck('id', 'name'));
                    }, 'Пользователь');
                    $filter->where(function ($query) {
                        $query->whereIn('point_id', Point::where('city_id', $this->input)->get()->pluck('point_id', 'name'));
                    }, 'Город')->select(City::all()->pluck('name', 'city_id'));
                }
                if (Admin::user()->isRole('user')) {
                    $filter->where(function ($query) {
                        $query->whereIn('point_id', Point::where('city_id', $this->input)->get()->pluck('point_id', 'name'));
                    }, 'Город')->select(\DB::table('delivery_waste')
                       ->leftJoin('point', 'delivery_waste.point_id', '=', 'point.point_id')
                       ->leftJoin('city', 'city.city_id', '=', 'point.city_id')
                       ->where('user_id', Admin::user()->id)
                       ->get()->pluck('name', 'city_id'));
                }

                $filter->where(function ($query) {
                    $query->whereIn('point_id', Point::where('name', 'like', '%' . $this->input . '%')->orWhere('address', 'like', '%' . $this->input . '%')->get()->pluck('point_id', 'name'));
                }, 'Пункт приема');
                $filter->is('waste_id', 'Втор. сырье')->select(Waste::all()->pluck('name', 'waste_id'));
                $filter->between('bulk', 'Объем (кг)');
                $filter->between('price', 'Цена');
                $filter->between('summa', 'Сумма');
                $filter->between('date_delivery', 'Дата сдачи')->datetime();
                $filter->equal('paid', 'Оплата')->select(['1' => 'Да', '0' => 'Нет',]);
                $filter->between('date_paid', 'Дата оплаты')->datetime();
            });
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form($isEdit)
    {
        return Admin::form(DeliveryWaste::class, function (Form $form) use ($isEdit) {
            if (Admin::user()->isRole('administrator')) {
                $form->select('user_id', 'Пользователь')->options(User::select('name', 'id')->get()->pluck('name', 'id'));
                $form->select('point_id', 'Пункт приема')->options(
                    \DB::table('city')
                       ->select(\DB::raw('point_id, concat(city.name, ". ", point.name, " (", point.address, ")") as name'))
                       ->leftJoin('point', 'city.city_id', '=', 'point.city_id')
                       ->whereNull('city.deleted_at')
                       ->whereNull('point.deleted_at')
                       ->orderBy('name')
                       ->get()->pluck('name', 'point_id')
                );
            }

            if (Admin::user()->isRole('user')) {
                $form->select('point_id', 'Пункт приема')->options(
                    \DB::table('city_user')
                       ->select(\DB::raw('point_id, concat(city.name, ". ", point.name, " (", point.address, ")") as name'))
                       ->leftJoin('city', 'city.city_id', '=', 'city_user.city_id')
                       ->join('point', 'city.city_id', '=', 'point.city_id')
                       ->where('user_id', Admin::user()->id)
                       ->where('city_user.date_to', '>', date('Y-m-d H:i:s'))
                       ->whereNull('city.deleted_at')
                       ->whereNull('point.deleted_at')
                       ->orderBy('name')
                       ->get()->pluck('name', 'point_id')
                );
            }
            $form->select('waste_id', 'Вторсырье')->options(Waste::all()->pluck('name', 'waste_id'));
            $form->decimal('bulk', 'Объем (кг)')->default(0);
            if ($isEdit) {
                $form->decimal('price', 'Цена')->readOnly();
                $form->decimal('summa', 'Сумма')->readOnly();
            }

            $form->datetime('date_delivery', 'Дата сдачи')->default(date('Y-m-d H:i:s'));

            if (Admin::user()->isRole('administrator')) {
                $form->radio('paid', 'Оплата')->options(['1' => 'Да', '0' => 'Нет'])->default('0');
                $form->datetime('date_paid', 'Дата оплаты')->default(date('Y-m-d H:i:s'));
            }

        });
    }

    public function store()
    {
        $newWaste = [];
        $errors = [];

        if (empty($_POST['date_delivery'])) {
            $errors['date_delivery'][] = 'Поле не может быть пустым.';
        }
        if (Admin::user()->isRole('administrator')) {
            if ($_POST['paid'] && empty($_POST['date_paid'])) {
                $errors['date_paid'][] = 'Поле не может быть пустым.';
            }
            $point = \DB::table('city_user')
                       ->select(['point_id', 'point.city_id'])
                       ->leftJoin('city', 'city.city_id', '=', 'city_user.city_id')
                       ->join('point', 'city.city_id', '=', 'point.city_id')
                       ->where('user_id', $_POST['user_id'])
                       ->where('city_user.date_to', '>', date('Y-m-d H:i:s'))
                       ->where('point.point_id', $_POST['point_id'])
                       ->whereNull('city.deleted_at')
                       ->whereNull('point.deleted_at')
                       ->first();
            if (empty($point->point_id)) {
                $errors['point_id'][] = 'Данный пользователь не может сдавать в этой точке приема.';
            }
        }
        if (Admin::user()->isRole('user')) {
            $point = \DB::table('city_user')
                       ->select(['point_id', 'point.city_id'])
                       ->leftJoin('city', 'city.city_id', '=', 'city_user.city_id')
                       ->join('point', 'city.city_id', '=', 'point.city_id')
                       ->where('user_id', Admin::user()->id)
                       ->where('city_user.date_to', '>', date('Y-m-d H:i:s'))
                       ->where('point.point_id', $_POST['point_id'])
                       ->whereNull('city.deleted_at')
                       ->whereNull('point.deleted_at')
                       ->first();
            if (empty($point->point_id)) {
                $errors['point_id'][] = 'Данный пользователь не может сдавать в этой точке приема.';
            }
        }

        if (!empty($point)) {
            $price = Price::where(['waste_id' => $_POST['waste_id'], 'city_id' => $point->city_id])->first();
            if (empty($price)) {
                $errors['point_id'][] = 'Нет цены для этого пункта приема.';
                $errors['waste_id'][] = 'Нет цены для этого вида отхода.';
            }
        }

        if (!empty($errors)) {
            return redirect (config('admin.prefix') . '/delivery-waste/create')
                                    ->withErrors($errors)
                                    ->withInput($_POST);
        }

        $summa = $_POST['bulk'] * $price->price;
        $dateDelivery = new \DateTime($_POST['date_delivery']);

        if (Admin::user()->isRole('administrator')) {
            $date_paid = new \DateTime($_POST['date_paid']);
            $newWaste = [
                'user_id' => $_POST['user_id'],
                'point_id' => $_POST['point_id'],
                'waste_id' => $_POST['waste_id'],
                'bulk' => $_POST['bulk'],
                'price' => $price->price,
                'summa' => $summa,
                'date_delivery' => $dateDelivery->format('Y-m-d H:i:s'),
                'paid' => $_POST['paid'],
                'date_paid' => $date_paid->format('Y-m-d H:i:s'),
            ];
        }

        if (Admin::user()->isRole('user')) {
            $newWaste = [
                'user_id' => Admin::user()->id,
                'point_id' => $_POST['point_id'],
                'waste_id' => $_POST['waste_id'],
                'bulk' => $_POST['bulk'],
                'price' => $price->price,
                'summa' => $summa,
                'date_delivery' => $dateDelivery->format('Y-m-d H:i:s'),
                'paid' => 0,
                'date_paid' => null,
            ];
        }

        DeliveryWaste::create($newWaste);
        return redirect (config('admin.prefix') . '/delivery-waste');
    }

    public function update(Request $request, $id)
    {
        $errors = [];

        if (empty($request->get('date_delivery'))) {
            $errors['date_delivery'][] = 'Поле не может быть пустым.';
        }
        if (Admin::user()->isRole('administrator')) {
            if ($request->get('paid') && empty($request->get('date_paid'))) {
                $errors['date_paid'][] = 'Поле не может быть пустым.';
            }
            $point = \DB::table('city_user')
                       ->select(['point_id', 'point.city_id'])
                       ->leftJoin('city', 'city.city_id', '=', 'city_user.city_id')
                       ->join('point', 'city.city_id', '=', 'point.city_id')
                       ->where('user_id', $request->get('user_id'))
                       ->where('city_user.date_to', '>', date('Y-m-d H:i:s'))
                       ->where('point.point_id', $request->get('point_id'))
                       ->whereNull('city.deleted_at')
                       ->whereNull('point.deleted_at')
                       ->first();
            if (empty($point->point_id)) {
                $errors['point_id'][] = 'Данный пользователь не может сдавать в этой точке приема.';
            }
        }
        if (Admin::user()->isRole('user')) {
            $point = \DB::table('city_user')
                       ->select(['point_id', 'point.city_id'])
                       ->leftJoin('city', 'city.city_id', '=', 'city_user.city_id')
                       ->join('point', 'city.city_id', '=', 'point.city_id')
                       ->where('user_id', Admin::user()->id)
                       ->where('city_user.date_to', '>', date('Y-m-d H:i:s'))
                       ->where('point.point_id', $request->get('point_id'))
                       ->whereNull('city.deleted_at')
                       ->whereNull('point.deleted_at')
                       ->first();
            if (empty($point->point_id)) {
                $errors['point_id'][] = 'Данный пользователь не может сдавать в этой точке приема.';
            }
        }

        if (!empty($point)) {
            $price = Price::where(['waste_id' => $request->get('waste_id'), 'city_id' => $point->city_id])->first();
            if (empty($price)) {
                $errors['point_id'][] = 'Нет цены для этого пункта приема.';
                $errors['waste_id'][] = 'Нет цены для этого вида отхода.';
            }
        }

        if (!empty($errors)) {
            return redirect (config('admin.prefix') . '/delivery-waste/' . $id . '/edit')
                                    ->withErrors($errors)
                                    ->withInput($request->get);
        }

        $summa = $request->get('bulk') * $price->price;
        $dateDelivery = new \DateTime($request->get('date_delivery'));
        $deliveryWaste = DeliveryWaste::where('delivery_waste_id', $id)->first();

        if (Admin::user()->isRole('administrator')) {
            $date_paid = new \DateTime($request->get('date_paid'));

            $deliveryWaste->user_id = $request->get('user_id');
            $deliveryWaste->point_id = $request->get('point_id');
            $deliveryWaste->waste_id = $request->get('waste_id');
            $deliveryWaste->bulk = $request->get('bulk');
            $deliveryWaste->price = $price->price;
            $deliveryWaste->summa = $summa;
            $deliveryWaste->date_delivery = $dateDelivery->format('Y-m-d H:i:s');
            $deliveryWaste->paid = $request->get('paid');
            $deliveryWaste->date_paid = $date_paid->format('Y-m-d H:i:s');
        }

        if (Admin::user()->isRole('user')) {
            $deliveryWaste->user_id = Admin::user()->id;
            $deliveryWaste->point_id = $request->get('point_id');
            $deliveryWaste->waste_id = $request->get('waste_id');
            $deliveryWaste->bulk = $request->get('bulk');
            $deliveryWaste->price = $price->price;
            $deliveryWaste->summa = $summa;
            $deliveryWaste->date_delivery = $dateDelivery->format('Y-m-d H:i:s');
            $deliveryWaste->paid = 0;
            $deliveryWaste->date_paid = null;
        }

        $deliveryWaste->save();
        return redirect (config('admin.prefix') . '/delivery-waste');
    }

    public function paid($id)
    {
        if (!Admin::user()->isRole('administrator')) {
            return redirect (route('index'));
        }

        if (Admin::user()->isAdministrator()) {
            $dw = DeliveryWaste::find($id);
            $dw->update([
                'paid' => 1,
                'date_paid' => date('Y-m-d H:i:s'),
            ]);
        }

        return redirect(url(config('admin.prefix') . '/delivery-waste'));
    }
}
