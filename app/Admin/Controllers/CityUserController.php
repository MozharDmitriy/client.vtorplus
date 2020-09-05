<?php

namespace App\Admin\Controllers;

use App\Models\CityUser;
use App\Models\City;
use App\User;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;

class CityUserController extends Controller
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
            $content->header('Доступные города');
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
            return redirect (config('admin.prefix') . '/city-user');
        }

        return Admin::content(function (Content $content) use ($id) {
            $content->header('Доступные города');
            $content->description('Редактирование');
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
            return redirect (config('admin.prefix') . '/city-user');
        }

        return Admin::content(function (Content $content) {
            $content->header('Доступные города');
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
        return Admin::grid(CityUser::class, function (Grid $grid) {
            $grid->disableExport();
            $grid->disableRowSelector();

            if (Admin::user()->isRole('user')) {
                $grid->disableFilter();
                $grid->disableActions();
                $grid->disableCreation();
                $grid->model()->where('user_id', Admin::user()->id);;
            }

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
                });
            }
            
            $grid->city_id('Город')->display(function($cityId) {
                return City::find($cityId)->name;
            })->sortable();
            $grid->date_to('Дата до')->display(function ($date) {
                return date('d.m.Y H:i', strtotime($date));
            })->sortable();

            $grid->filter(function ($filter) {
                $filter->disableIdFilter();
                $filter->where(function ($query) {
                    $query->whereIn('id', User::where('name', 'like', '%' . $this->input . '%')->get()->pluck('id', 'name'));
                }, 'Пользователь');
                $filter->where(function ($query) {
                    $query->whereIn('city_id', City::where('name', 'like', '%' . $this->input . '%')->get()->pluck('city_id', 'name'));
                }, 'Город');
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
        return Admin::form(CityUser::class, function (Form $form) {
            $form->select('user_id', 'Пользователь')->options(User::select('name', 'id')->get()->pluck('name', 'id'));
            $form->select('city_id', 'Город')->options(City::all()->pluck('name', 'city_id'));
            $form->datetime('date_to', 'Дата до');
        });
    }
}
