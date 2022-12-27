<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

abstract class BaseController extends Controller
{
    protected $query;
    protected $model;
    protected $request;

    public function __construct()
    {
        $this->request = request();
        $this->setModel();
        $this->setQuery();
    }

    abstract public function setModel();

    public function setQuery()
    {
        $this->query = $this->model->query();
    }

    public function index()
    {
        if(method_exists($this,'appendFilter')){
            $this->appendFilter();
        }
        $result = $this->query->get();
        return response()->json($result);
    }

    public function store(Request $request)
    {
        $user_id = Auth::id();

        $data = $request->only($this->model->getFillable());
        if ($user_id) {
            $data = array_merge($data, ['user_id' => $user_id]);
        }

        $result = $this->query->create($data);

        return $this->resultSuccess($result, __('message.created_success'));
    }

    public function show(Request $request, $id)
    {
        $item = $this->query->where('id',$id)->first();

        $userId = $request->user_id;
        if(!is_null($userId)){
            $item->user_id = $userId;
        }

        if (!$item) {
            return $this->notFoundMessage();
        }

        if (method_exists($this, 'afterFind')) {
            $item = $this->afterFind($item);
        }

        return response()->json($item);
    }

    public function update(Request $request, $id)
    {
        $data = $request->all();

        if (isset($data['id'])) {
            unset($data['id']);
        }

        $item = $this->query->where(['id' => $id])->first();
        if (!$item) {
            return $this->notFoundMessage();
        }

        $item->fill($data);
        $result = $item->update();

        return $this->resultSuccess($result,__('message.update_success'));
    }

    public function destroy(Request $request, $id)
    {
        $item = $this->query->find($id);

        if (!$item) {
            return $this->notFoundMessage();
        }

        $item->delete();

        return response()->json([
            'message' => __('message.delete_success')
        ], 200);
    }

    public function resultSuccess($data, $message="Success")
    {
        return response()->json([
            'message' => $message,
            'data' => $data
        ], 200);
    }

    public function notFoundMessage()
    {
        return response()->json([
            'message' => 'Not found'
        ], 404);
    }
}
