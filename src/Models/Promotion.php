<?php

    namespace BbqData\Models;

    use BbqData\Contracts\Model;

    class Promotion extends Model{

    
        /**
         * Payment table
         *
         * @var string
         */
        protected $table = 'promotions';

        /**
         * Only shield ids from mass-filling
         * 
         * @var Array
         */
        protected $guarded = ['id'];

        /**
         * A promotion belongs to a product
         *
         * @return void
         */
        public function product()
        {
            return $this->belongsTo( Product::class );
        }
        
        /**
         * A production may belong to a product variation
         *
         * @return void
         */
        public function variation()
        {
            return $this->hasOne( ProductVariation::class );
        }

    }
