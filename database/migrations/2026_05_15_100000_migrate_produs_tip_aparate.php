<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // TIP_APARATE (3) este eliminat — adresele cu aparate (dozatoare cu bidoane)
        // sunt acum tot abonamente lunare (tip=1). Comportamentul e identic:
        // isAbonament() returna false pentru 3, acum returneaza true pt 1.
        DB::table('produs')->where('abonament', 3)->update(['abonament' => 1]);
    }

    public function down(): void
    {
        // Nu putem recupera ce era tip=3 vs tip=1 fara informatii suplimentare.
    }
};
