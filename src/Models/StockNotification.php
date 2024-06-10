<?php

    namespace BbqData\Models;

    class StockNotification extends PostNotification
    {


        /**
         * Customers table
         *
         * @var string
         */
        protected $table = 'post_notifications';

        /**
         * An order row has a Product
         *
         * @return void
         */
        public function product()
        {
            return $this->belongsTo('BbqData\Models\Product', 'post_id', 'ID' );
        }



    }
