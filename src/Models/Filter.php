<?php

    namespace BbqData\Models;

    use BbqData\Contracts\Model;

    class Filter extends Model
    {
        /**
         * Customers table
         *
         * @var string
         */
        protected $table = 'filters';

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
        public function indexes()
        {
            return $this->hasMany('BbqData\Models\Index');
        }


    }
