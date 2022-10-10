<?php

    //auto-loads all .php files in these directories.
    $includes = array( 
        'Classes/Models/Scopes',
        'Classes/Models/Casts',
        'Classes/Models'
    );

    $root = __DIR__ . '/';
    require_once( $root . 'Classes/Helpers/Price.php' );
    require_once( $root . 'Classes/Contracts/Model.php' );

    foreach( $includes as $inc ){
        
        $files = glob( $root.$inc.'/*.php' );

        foreach ( $files as $file ){

            require_once( $file );

        }
    }
