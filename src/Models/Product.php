<?php

    namespace BbqData\Models;


    use BbqData\Models\Handlers\Stock;
    use BbqData\Models\Scopes\ProductScope;

    class Product extends Post
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
            return $this->hasMany( ProductVariation::class, 'product_id', 'ID' );
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
            $result = static::table('postmeta')
                ->where('meta_key', 'product-subtitle') 
                ->where('post_id', $this->ID )->first();

            if( !is_null( $result ) && isset( $result->meta_value ) && $result->meta_value !== '' ){
                $title .= ' '.$result->meta_value;
            }
            
            return $title;
        }


        /**
         * Return a product thumbnail
         *
         * @return void
         */
        public function getThumbnailAttribute()
        {
            $result = '';
            //query subtitle:
            $result = static::table('postmeta')
                ->where('meta_key', '_thumbnail_id') 
                ->where('post_id', $this->ID )->first();


            if( !is_null( $result ) && isset( $result->meta_value ) && $result->meta_value !== '' ){
                $thumb_id = $result->meta_value;
                $result = static::table('postmeta')
                                ->where('meta_key', '_wp_attachment_metadata')
                                ->where('post_id', $thumb_id )
                                ->first();

                if( !is_null( $result ) && isset( $result->meta_value ) && $result->meta_value !== '' ){
                    $value = unserialize( $result->meta_value );
                    return env('WP_URL') . '/wp-content/uploads/' . $value['file'];
                }
            }
            
            return $result;
        }
    }
