<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Cms\Cms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CmsController extends Controller
{
     public function index()
    {
       
        $data['name'] = "Cms Page";
        
        
        return view('cms.index', $data);
    }
    public function getCms(Request $request)
    {
       
        $perPage = $request->input('per_page', 10);
        $searchTerm = $request->input('search', '');
    


        $query = Cms::query();



      

            if (!empty($searchTerm)) {
            $query->where('title', 'LIKE', '%' . $searchTerm . '%');
        }
            $users = $query->paginate($perPage);
            $users->getCollection()->transform(function($user) {
            $user->actions = [
                'edit' => route('cms.edit', $user->id),
                'delete' => route('cms.destroy', $user->id),
            ];
            return $user;
        });
    
        return response()->json($users);
    }


    
public function show($id)
{
    
    $customer = Cms::with(['audits.user'])->findOrFail($id);


    $audits = $customer->audits->map(function ($audit) {
        $audit->user_name = $audit->user ? $audit->user->name : null;
        return $audit;
    });

   

   

    return response()->json([
        'title' => $customer->title,
        'type' => $customer->type,
        'meta_tags' => $customer->meta_tags,
        'meta_keywords' => $customer->meta_keywords,
        'content' => $customer->content,
       
      
        
       
        'audits' => $audits,
    ]);
}

public function create(){
   
    $data['name'] = "Cms Page";
    
    return view('cms.create', $data);
}
public function edit($edit){
 
    $data['record'] = Cms::findOrFail($edit);


    $data['name'] = "Cms Page";
    
    return view('cms.create', $data);
}










public function store(Request $request)
{
    try {
        $validated = $request->validate([
            'type'          => 'required|string|max:100',
            'title'         => 'required|string|max:500',
            'slug'          => 'required|string|max:255|unique:cms,slug',
            'meta_tags'     => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'content'       => 'nullable|string',
            'is_active'     => 'nullable|boolean',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('cms-images', 'public');
            $validated['image'] = $path;
        }

        $validated['is_active'] = $request->has('is_active');

        $cms = Cms::create($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'CMS Page Created Successfully',
                'data' => $cms
            ]);
        }

        return redirect()->route('cms.index')->with('success', 'CMS Page Created Successfully');

    } catch (\Exception $e) {
        if ($request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating CMS page.',
                'error' => $e->getMessage()
            ], 500);
        }

        return redirect()->back()->with('error', 'Error creating CMS page: ' . $e->getMessage())->withInput();
    }
}



public function update(Request $request, $id)
{
   
    $cms = Cms::findOrFail($id);

    $validated = $request->validate([
        'type'          => 'required|string|max:100',
        'title'         => 'required|string|max:500',
        'slug'          => 'required|string|max:255|unique:cms,slug,' . $id,
        'meta_tags'     => 'nullable|string',
        'meta_keywords' => 'nullable|string',
       
        'content'       => 'nullable|string',
       
    ]);

    if ($request->hasFile('image')) {
        $path = $request->file('image')->store('cms-images', 'public');
        $validated['image'] = $path;
    }

    $validated['is_active'] = $request->has('is_active');

    $cms->update($validated);

    return response()->json(['message' => 'CMS page updated successfully', 'data' => $cms], 200);
}


public function destroy($id)
    {


        $driver = Cms::findOrFail($id);



      


     


        $driver->delete();
        
        return redirect()->route('cms.index')
            ->with('success', 'Cms deleted successfully');
    }

    public function bulkDestroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:cms,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }



        Cms::whereIn('id', $request->ids)->delete();



    



        return response()->json([
            'success' => true, 
            'message' => count($request->ids) . ' cms deleted successfully'
        ]);
    }
}
