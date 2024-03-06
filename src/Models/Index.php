<?php

    namespace BbqData\Models;

    use BbqData\Contracts\Model;

    class Index extends Model
    {
        /**
         * Customers table
         *
         * @var string
         */
        protected $table = 'indexes';

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
        public function filter()
        {
            return $this->belongsTo('BbqData\Models\Filter');
        }


    }
