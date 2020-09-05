<?php

namespace App\Admin\Controllers;

use App\Models\Point;
use App\Models\City;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;

class PointController extends Controller
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

        return Admin::content(function (Content $content) {
            $content->header('Пункты приема');
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
            $content->header('Пункты приема');
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
            $content->header('Пункты приема');
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
        return Admin::grid(Point::class, function (Grid $grid) {
            $grid->disableExport();
            $grid->disableRowSelector();

            $grid->point_id('ID')->sortable();
            $grid->city()->name('Город')->sortable();
            $grid->name('Наименование')->sortable();
            $grid->address('Адрес')->sortable();
            $grid->contact('Контакты')->sortable();

            $grid->filter(function ($filter) {
                $filter->disableIdFilter();
                $filter->is('city_id', 'Город')->select(City::all()->pluck('name', 'city_id'));
                $filter->like('name', 'Наименование');
                $filter->like('address', 'Адрес');
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
        return Admin::form(Point::class, function (Form $form) {
            $form->display('point_id', 'ID');
            $form->select('city_id', 'Город')->options(City::all()->pluck('name', 'city_id'));
            $form->text('name', 'Наименование');
            $form->text('address', 'Адрес');
            $form->text('contact', 'Контакты');
        });
    }
}
