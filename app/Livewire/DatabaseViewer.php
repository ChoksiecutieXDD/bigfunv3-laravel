<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseViewer extends Component
{
    public $tables = [];
    public $selectedTable = null;
    public $columns = [];
    public $tableData = [];

    public function mount()
    {
        // 1. Fetch all tables in the database
        $tablesList = DB::select('SHOW TABLES');

        foreach ($tablesList as $tableObj) {
            $this->tables[] = array_values((array)$tableObj)[0];
        }

        // 2. Default to the first table if there are any
        if (!empty($this->tables)) {
            $this->selectTable($this->tables[0]);
        }
    }

    // 3. This runs instantly without page reloads when you click a sidebar link
    public function selectTable($tableName)
    {
        // Security check: ensure the table actually exists
        if (in_array($tableName, $this->tables)) {
            $this->selectedTable = $tableName;

            // Laravel's Schema builder makes getting columns super easy
            $this->columns = Schema::getColumnListing($tableName);

            // Get Top 100 rows securely
            $this->tableData = DB::table($tableName)
                ->limit(100)
                ->get()
                ->map(fn($row) => (array) $row) // Convert objects to arrays for the view
                ->toArray();
        }
    }

    public function render()
    {
        return view('livewire.database-viewer', [
            'dbName' => env('DB_DATABASE', 'bigfun') // Get DB name from .env
        ]);
    }
}
