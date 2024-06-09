<?php

namespace App\Boilerplate\Controllers;

use App\boilerplate\Models\ModelStd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiController
{
    var $model = ModelStd::class;
    var $with = [];
    var $keyword_fields = [];

    var $allowed_filter = [];

    public function __construct() {
        if (is_string($this->model)) {
            $this->model = new $this->model;
        }
    }

    public function json_response($data=[], $message='success', int $code = 200) {
        $data = [
            'status' => $code,
            'message' => $message,
            'data' => $data,
        ];
        return response()->json($data, $code);
    }

    public function filters(Request $request): void
    {
        $params = $request->all();
        if (sizeof($this->keyword_fields) > 0) {
            if ($request->get('keyword')) {
                $this->model = $this->model->where(function ($query) use ($request) {
                    foreach ($this->keyword_fields as $field) {
                        $query->orWhere($field, 'like', '%' . $request->get('keyword') . '%');
                    }
                });

            }
        }
        foreach ($params as $key => $value) {
            if (in_array($key, $this->allowed_filter)) {
                if ($key != 'keyword') {
                    $this->model = $this->model->where($key, '=', $value);
                }
            }
        }
    }

    public function list(Request $request) {
        $this->model = $this->model->with($this->with);
        $this->filters($request);
        $this->model = $this->model->get();
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->list($request);

        return $this->json_response($this->model);
    }

    public function create(Request $request) {
        $data = $request->all();
        return $this->model->create($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $data = $this->create($request);
            DB::commit();

            return $this->show($data->id);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->json_response([], $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $data = $this->model->with($this->with);
        $data = $data->find($id);
        if ($data == null) {
            return $this->json_response([], 'Not Found', 404);
        }
        return $this->json_response($data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            DB::beginTransaction();

            $data = $this->model->find($id);
            if ($data == null) {
                return $this->json_response([], 'Not Found', 404);
            }
            $data->update($request->all());
            DB::commit();

            return $this->json_response($data);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->json_response([], $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            DB::beginTransaction();

            $data = $this->model->find($id);
            if ($data == null) {
                return $this->json_response([], 'Not Found', 404);
            }
            $data->delete();
            DB::commit();
            return $this->json_response();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->json_response([], $e->getMessage(), 500);
        }
    }
}
