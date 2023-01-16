<?php

    namespace BbqData\Models;

    use BbqData\Helpers\Price;
    use BbqData\Contracts\Model;
    use BbqData\Models\Handlers\Stock;

    class OrderRow extends Model{

    
        /**
         * Order table
         *
         * @var string
         */
        protected $table = 'order_rows';

        protected $fillable = [
            'description',
            'price',
            'quantity',
            'vat',
            'on_sale',
            'product_id',
            'stock_reduced',
            'points_spent',
            'points_earned',
            'product_variation_id'
        ];

        /**
         * An order row has a Product variation
         *
         * @return void
         */
        public function variation()
        {
            return $this->belongsTo('BbqData\Models\ProductVariation', 'product_variation_id', 'id' );
        }

        /**
         * An order row has a Product
         *
         * @return void
         */
        public function product()
        {
            return $this->belongsTo('BbqData\Models\Product', 'product_id', 'ID' );
        }

        /**
         * Return the correct stock handler,
         * depending on if we're dealing with a variation     
         *
         * @return Product / ProductVariation
         */
        public function stock()
        {
            if( $this->product_variation_id == 0 ){
                $product = $this->product;
            }else{
                $product = $this->variation;
            }

            //if the product wasn't found
            if( is_null( $product ) ){
                return new Stock( 'dummy', $this );
            }


            return $product->stock();
        }

        /**
         * Return this row's total
         *
         * @return float
         */
        public function getTotalAttribute()
        {
            return $this->price * $this->quantity;
        }

        /**
         * Return the formatted price attribute
         *
         * @return string
         */
        public function getFormattedPriceAttribute()
        {
            return Price::format( $this->price );
        }

        /**
         * Return the formatted Total
         *
         * @return string
         */
        public function getFormattedTotalAttribute()
        {
            return Price::format( $this->total );
        }

        /**
         * Return the products thumbnail
         *
         * @return void
         */
        public function getThumbnailAttribute()
        {
            if( function_exists( 'get_the_post_thumbnail_url' ) ){
                $thumb = \get_the_post_thumbnail_url( $this->product_id, 'thumbnail' );
                $thumb = \get_site_url() . $thumb;
                //for localhost
                $thumb = str_replace( 'bbquality/bbquality/', 'bbquality/', $thumb ); 
                return $thumb;
            }            
        }

        /**
         * Return the full product id
         *
         * @return string
         */
        public function getFullProductIdAttribute()
        {
            $id = $this->product_id;
            if( !is_null( $this->product_variation_id ) && $this->product_variation_id != '' ){
                $id .= $this->product_variation_id;
            }

            return $id;
        }

        /**
         * Return the correct Product Class,
         * if the class exists    
         *
         * @return Product / ProductVariation
         */
        public function getProductClassAttribute()
        {
            if( class_exists( '\BbqOrders\Helpers\Product' ) ){
                return \BbqOrders\Helpers\Product::get( $this->product_id, $this->product_variation_id );
            }
        }


        /**
         * Filter the description
         *
         * @param string $value
         * @return string
         */
        public function getDescriptionAttribute( $value )
        {
            $var = $this->variation;
            if( !is_null( $var ) && substr( $value, -3 ) !== 'gr.' ){
                
                $value .= ' '.$var->portion.'gr.';
            }

            return $value;
        }


    }