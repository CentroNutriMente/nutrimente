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

    /*
     | Sicurezza: i PDF (referti, fatture, moduli compilati) incorporano contenuto
     | inserito dagli utenti. Disabilitiamo esplicitamente il fetch di risorse remote
     | (no SSRF) e l'esecuzione di PHP inline, senza affidarci ai default impliciti.
     */
    'enable_remote' => false,
    'enable_php'    => false,

    'options' => [
        'isRemoteEnabled' => false,
        'isPhpEnabled'    => false,
    ],

];
