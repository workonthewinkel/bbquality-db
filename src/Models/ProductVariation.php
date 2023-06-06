<?php

    namespace BbqData\Models;

    use BbqData\Contracts\Model;
    use BbqData\Models\Handlers\Stock;
    use BbqData\Models\Scopes\ProductScope;

    class ProductVariation extends Model
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
        protected $table = 'product_meta';

        /**
         * Only the id and order_id are guarded
         *
         * @var array
         */
        protected $guarded = ['id'];



        /**
         * An order has a payment:
         *
         * @return void
         */
        public function product()
        {
            return $this->belongsTo('BbqData\Models\Product', 'product_id' , 'ID');
        }
        

        /**
         * Return a stock object
         *
         * @return void
         */
        public function stock()
        {
            return new Stock( 'variation', $this );
        }


        /**
         * Return the title;
         *
         * @return String
         */
        public function getTitleAttribute()
        {
            $title = $this->product->title;
            $title .= ' - '.$this->portion.'gr';
            return $title;
        }
        
    }
