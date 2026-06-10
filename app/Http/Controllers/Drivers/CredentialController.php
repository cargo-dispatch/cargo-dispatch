<?php

namespace App\Http\Controllers\Drivers;

use App\Http\Controllers\Controller;
use App\Models\Drivers\Driver;
use App\Models\Drivers\DriverCredential;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class CredentialController extends Controller
{
    // Store driver ID from session if available
    protected $driverId;

    public function __construct()
    {
        $this->driverId = session('driver_id');
    }

    public function index($id = null)
    {
        
        if ($id) {
            session(['driver_id' => $id]);
            $this->driverId = $id;
        } else {
         
            $id = session('driver_id');
            $this->driverId = $id;
        }

       

        $data['results'] = Driver::find($id);
        $data['name'] = "Credentialing";

        return view('drivers.credentials.index', $data);
    }

    public function getCredentials(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $searchTerm = $request->input('search', '');
        $sortColumn = $request->input('sort_column', 'id');
        $sortOrder = $request->input('sort_order', 'asc');

        // Use driver_id from request or fallback to session
        $driverId = $request->input('driver_id') ?? $this->driverId;

        $query = DriverCredential::query();

        if ($driverId) {
            $query->where('driver_id', $driverId);
        }

        if (!empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('file', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        $query->orderBy($sortColumn, $sortOrder);

        $credentials = $query->paginate($perPage);

        $credentials->getCollection()->transform(function ($credential) {
            if ($credential->file) {
                $files = json_decode($credential->file);
                if (!$files) {
                    $credential->file = '[]';
                }
            } else {
                $credential->file = '[]';
            }

            $credential->actions = [
                'edit' => route('credentials.edit', $credential->id),
                'delete' => route('credentials.destroy', $credential->id),
            ];

            return $credential;
        });

        return response()->json($credentials);
    }

    public function create($driverId = null)
    {
        // Get driverId from parameter or fallback to session
        if (!$driverId) {
            $driverId = $this->driverId;
        }

        if (!$driverId) {
            return redirect()->route('some.route')->with('error', 'Driver ID not found.');
        }

        $driver = Driver::findOrFail($driverId);

        $data = [
            'driver' => $driver,
            'name' => "Add Credentialing",
        ];

        return view('drivers.credentials.create', $data);
    }

    public function edit($id)
    {
       
        $data['results'] = DriverCredential::findOrFail($id);
        $data['name'] = 'Edit Credentialing';

        return view('drivers.credentials.create', $data);
    }

    public function store(Request $request, $driverId = null)
    {
        // Get driverId from param or session
        if (!$driverId) {
            $driverId = $this->driverId;
        }

        if (!$driverId) {
            return redirect()->route('some.route')->with('error', 'Driver ID not found.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'expiry_date' => 'required|date',
            'files.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048'
        ]);

        $filePaths = [];
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $fileName = time() . '_' . $file->getClientOriginalName();
                $destinationPath = 'assets/uploads/driver_files';

                if (!file_exists(public_path($destinationPath))) {
                    mkdir(public_path($destinationPath), 0755, true);
                }

                $file->move(public_path($destinationPath), $fileName);

                $filePaths[] = $destinationPath . '/' . $fileName;
            }
        }

        DriverCredential::create([
            'driver_id' => $driverId,
            'title' => $request->title,
            'expiry_date' => $request->expiry_date,
            'file' => json_encode($filePaths),
        ]);

        return redirect()->route('credentials.index', $driverId)->with('success', 'Credential added successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'expiry_date' => 'required|date',
            'files.*' => 'sometimes|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048'
        ]);

        $credential = DriverCredential::findOrFail($id);

        $filePaths = [];

        if ($request->has('keep_files')) {
            $keepFiles = json_decode($request->keep_files, true);
            if (is_array($keepFiles)) {
                $filePaths = $keepFiles;
            }
        }

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $fileName = time() . '_' . $file->getClientOriginalName();
                $destinationPath = 'assets/uploads/driver_files';

                if (!file_exists(public_path($destinationPath))) {
                    mkdir(public_path($destinationPath), 0755, true);
                }

                $file->move(public_path($destinationPath), $fileName);

                $filePaths[] = $destinationPath . '/' . $fileName;
            }
        }

        $credential->update([
            'title' => $request->title,
            'expiry_date' => $request->expiry_date,
            'file' => json_encode($filePaths),
        ]);

        return redirect()->route('credentials.index', $credential->driver_id)->with('success', 'Credential updated successfully.');
    }

    public function destroy($id)
    {
        $driverCredential = DriverCredential::findOrFail($id);
        $driverCredential->delete();

        return redirect()->route('driver.index')
            ->with('success', 'Driver deleted successfully');
    }

    public function preview(Request $request)
    {
        $filePath = $request->input('file');
        $fileName = $request->input('name', 'file');

        if (!$filePath || str_contains($filePath, '..')) {
            abort(404, 'File not found');
        }

        if (!File::exists(public_path($filePath))) {
            abort(404, 'File not found');
        }

        $extension = File::extension(public_path($filePath));

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'pdf'])) {
            return view('file.preview', [
                'filePath' => asset($filePath),
                'fileName' => $fileName,
                'fileType' => $extension,
                'isPreviewable' => true
            ]);
        } else {
            return view('file.preview', [
                'filePath' => asset($filePath),
                'fileName' => $fileName,
                'fileType' => $extension,
                'isPreviewable' => false
            ]);
        }
    }

    public function download(Request $request)
    {
        $filePath = $request->input('file');

        if (!$filePath || str_contains($filePath, '..')) {
            abort(404, 'File not found');
        }

        if (!File::exists(public_path($filePath))) {
            abort(404, 'File not found');
        }

        $fileName = basename($filePath);
        $mimeType = File::mimeType(public_path($filePath));

        return Response::download(public_path($filePath), $fileName, ['Content-Type' => $mimeType]);
    }
}
