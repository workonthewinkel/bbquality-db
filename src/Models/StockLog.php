<?php

    namespace BbqData\Models;

    use BbqData\Contracts\Model;

    class StockLog extends Model
    {
        
        /**
         * Customers table
         *
         * @var string
         */
        protected $table = 'stock_log';

        /**
         * Only the id and order_id are guarded
         *
         * @var array
         */
        protected $guarded = ['id'];


    
    }