<?php

    namespace BbqData\Models;

    use BbqData\Contracts\Model;

    class Affiliate extends Model
    {
        /**
         * Customers table
         *
         * @var string
         */
        protected $table = 'affiliates';

        /**
         * Only the id is guarded
         *
         * @var array
         */
        protected $guarded = ['id'];

    }
