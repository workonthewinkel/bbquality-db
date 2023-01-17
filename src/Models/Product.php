<?php

    namespace BbqData\Models;


    use BbqData\Contracts\Model;
    use BbqData\Models\Handlers\Stock;
    use BbqData\Models\Scopes\ProductScope;

    class Product extends Model
    {
        
        /**
         * Don't keep timestamps
         *
         * @var boolean
         */
        public $timestamps = false;

        /**
         * Customers table
         *
         * @var string
         */
        protected $table = 'posts';

        /**
         * Only the id and order_id are guarded
         *
         * @var array
         */
        protected $guarded = ['id'];

        /**
         * When creating a product, always take the scope with you
         *
         * @return void
         */
        protected static function boot() {
            parent::boot();
            static::addGlobalScope( new ProductScope() );
        }


        /**
         * An order has a payment:
         *
         * @return void
         */
        public function variations()
        {
            return $this->hasMany('BbqData\Models\ProductVariation', 'product_id', 'ID' );
        }


        /**
         * Return a stock object
         *
         * @return void
         */
        public function stock()
        {
            return new Stock( 'product', $this );
        }
        
        /**
         * Return a product title
         *
         * @return void
         */
        public function getTitleAttribute()
        {
            $title = $this->post_title;

                        //query subtitle:
/*            $result = Capsule::table('bbquality_postmeta')
                ->where('meta_key', 'product-subtitle') 
                ->where('post_id', $this->ID )->first();

            dd( $result );
*/

            if( function_exists( 'get_post_meta' ) ){
                $subtitle = \get_post_meta( $this->ID, 'product-subtitle', true );
                if( $subtitle && $subtitle !== '' ){
                    $title .= ' '.$subtitle;
                }
            }    
            
            return $title;
        }


    }
