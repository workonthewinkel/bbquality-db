<?php

    namespace BbqData\Models;

    use BbqData\Contracts\Model;

    class StockNotification extends Model
    {


        /**
         * Customers table
         *
         * @var string
         */
        protected $table = 'stock_notifications';

        /**
         * Only the id is guarded
         *
         * @var array
         */
        protected $guarded = ['id'];

        /**
         * Return the user connection
         *
         * @return void
         */
        public function user()
        {
            return $this->belongsTo( User::class, 'user_id', 'ID' );    
        }

        /**
         * An order row has a Product
         *
         * @return void
         */
        public function product()
        {
            return $this->belongsTo( Product::class, 'product_id', 'ID' );
        }



    }
