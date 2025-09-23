<?php

    namespace BbqData\Models;

    use BbqData\Helpers\Price;
    use BbqData\Contracts\Model;
    use BbqData\Helpers\Discount;
    use BbqData\Models\Handlers\Stock;
	use BbqData\Models\Scopes\VisibleOrderRowScope;

    class OrderRow extends Model{

    
        /**
         * Order table
         *
         * @var string
         */
        protected $table = 'order_rows';
        protected $guarded = ['id'];


		/**
         * When creating a product, always take the scope with you
         *
         * @return void
         */
        protected static function boot() {
            parent::boot();
            static::addGlobalScope( new VisibleOrderRowScope() );
        }

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
         * A order row belongs to an order.
         * 
         * @return Order
         */
        public function order()
        {
            return $this->belongsTo('BbqData\Models\Order', 'order_id', 'id' );
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
            $total = $this->price * $this->quantity;
            $stacked_discount = Discount::calculate( $this );
            return $total - $stacked_discount;
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
         * Return the savings of this order row
         *
         * @return float
         */
        public function getSavingsAttribute()
        {
            if( is_null( $this->original_price ) ){
                return 0;
            }

            return ( $this->original_price - $this->price );
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
         * Return wether or not this order row has a stacked discount:
         *
         * @return bool
         */
        public function getHasStackedDiscountAttribute()
        {
            //no variation products (for now:)
            if( !is_null( $this->product_variation_id ) && $this->product_variation_id !== 0 ){
                return false;
            }

            //if the discount type is sale or empty, it's a no:
            if( is_null( $this->discount_type ) || $this->discount_type == 'sale' ){
                return false;
            }
            
            //else, calculate; if it's a zero or lower, it's a no.
            if( Discount::calculate( $this ) <= 0 ){
                return false;
            }

            return true;

        }


        /**
         * Return the name of this order row's discount
         *
         * @return string
         */
        public function getDiscountNameAttribute()
        {
            $discount = Discount::find( $this->discount_type );
            
            if( is_null( $this->discount_type ) || is_null( $discount )){
                return;
            }

            return $discount['name'];
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
				if( substr( $thumb, 0, 5 ) !== 'https' ){ 
                	$thumb = \get_site_url() . $thumb;
				}
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


    }
