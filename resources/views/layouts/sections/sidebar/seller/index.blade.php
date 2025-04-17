 {{-- Comercio/Vendedor --}}
 @extends('layouts/sections/sidebar/index')
 @section('user-type', 'Comercio/Vendedor')
 @section('body')
     <li class="nav-item has-treeview">
         <a href="{{ route('directory.index', ['countryCode' => strtolower(session('countryCode'))]) }}"
             class="nav-link {{ !Route::is('directory.index') ?: 'active' }}">
             <i class="nav-icon fas fa-university"></i>
             <p>Directorio</p>
         </a>
     </li>
     <li class="nav-item has-treeview">
         <a href="#"
             class="nav-link {{ !Route::is('seller.cartonGenerate', 'bingo.ctnsSold', 'bingo.ctnsAwarded', 'bingo.reportSalesRooms', 'bingo.reportSalesByRoom') ?: 'active' }} ">
             <i class="nav-icon fa-solid fa-hat-wizard"></i>
             <p>Bingo
                 <i class="right fas fa-angle-left"></i>
             </p>
         </a>
         <ul class="nav nav-treeview">
             <li class="nav-item">
                 <a href="{{ route('seller.cartonGenerate') }}"
                     class="nav-link {{ !Route::is('seller.cartonGenerate') ?: 'active' }}">
                     <i class="fa-solid fa-caret-right nav-icon"></i>
                     <p>Generar Cartón</p>
                 </a>
             </li>
             <li class="nav-item">
                 <a href="{{ route('bingo.ctnsSold') }}" class="nav-link {{ !Route::is('bingo.ctnsSold') ?: 'active' }}">
                     <i class="fa-solid fa-caret-right nav-icon"></i>
                     <p>Cartones</p>
                 </a>
             </li>
             <li class="nav-item">
                 <a href="{{ route('bingo.ctnsAwarded', ['type' => 'winners', 'dateFilter' => 'today']) }}"
                     class="nav-link {{ !Route::is('bingo.ctnsAwarded') ?: 'active' }}">
                     <i class="fa-solid fa-caret-right nav-icon"></i>
                     <p>Ganadores</p>
                 </a>
             </li>
             <li class="nav-item">
                 <a href="{{ route('bingo.reportSalesRooms') }}"
                     class="nav-link {{ !Route::is('bingo.reportSalesRooms', 'bingo.reportSalesByRoom') ?: 'active' }}">
                     <i class="fa-solid fa-caret-right nav-icon"></i>
                     <p>Reporte de Ventas</p>
                 </a>
             </li>
         </ul>
     </li>
 @endsection
