<?php

    namespace BbqData\Contracts;

    use Illuminate\Database\Capsule\Manager as Capsule;
    use Illuminate\Database\Eloquent\Model as OriginalModel;

    abstract class Model extends OriginalModel{

        /**
         * Custom prefix
         *
         * @var string
         */
        protected $prefix = false;

        /**
         * Table
         *
         * @var string
         */
        protected $table = '';

        
        /**
         * Constructor
         */
        public function __construct( array $attributes = array() )
        {
            parent::__construct( $attributes );

            global $wpdb;
            if( !is_null( $wpdb ) ){
                $this->table = $wpdb->prefix . $this->table;
            }

        }


        public static function table( $table_name = null )
        {
            if( is_null( $table_name ) ){
                $table_name = $this->table;
            }
            
            //DB means we're 100% in laravel context. Capsule means we're working from WordPress.            
            if( class_exists( '\\DB' ) ){
                return \DB::table( $table_name );
            }else{
                return Capsule::table( 'bbquality_'.$table_name );
            }
        }

    }