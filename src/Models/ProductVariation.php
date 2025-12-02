<?php

    namespace BbqData\Models;

    use BbqData\Contracts\Model;
    use BbqData\Models\Handlers\Stock;
    use BbqData\Models\Scopes\ProductScope;
	use Illuminate\Database\Eloquent\SoftDeletes;

    class ProductVariation extends Model
    {
		use SoftDeletes;

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
            return $this->hasOne('BbqData\Models\Product', 'ID', 'product_id' );
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
