<?php

namespace App\Admin\Controllers;

use App\Models\City;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;

class CityController extends Controller
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
            $content->header('Города');
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
            $content->header('Города');
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
            return redirect (route('index'));
        }

        return Admin::content(function (Content $content) {
            $content->header('Города');
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
        return Admin::grid(City::class, function (Grid $grid) {
            $grid->disableExport();
            $grid->disableRowSelector();

            $grid->city_id('ID')->sortable();
            $grid->name('Наименование')->sortable();

            $grid->filter(function ($filter) {
                $filter->disableIdFilter();
                $filter->like('name', 'Наименование');
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
        return Admin::form(City::class, function (Form $form) {

            $form->display('city_id', 'ID');
            $form->text('name', 'Наименование');
        });
    }
}
