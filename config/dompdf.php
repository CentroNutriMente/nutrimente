<?php

return [

    /*
     | Su Aruba la cartella public/ è stata spostata nel docroot, quindi
     | base_path('public') (default di laravel-dompdf) non esiste e il
     | ServiceProvider lancia "Cannot resolve public path".
     | Puntiamo il base path a una cartella che esiste sempre: non servono
     | asset pubblici perché i PDF (volantino/fatture/referti) usano risorse
     | inline / data-uri.
     */
    'public_path' => base_path(),

];
