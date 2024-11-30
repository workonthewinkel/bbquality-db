<?php

    namespace BbqData\Models;

    use BbqData\Contracts\Model;

    class Loyalty extends Model
    {
        /**
         * Customers table
         *
         * @var string
         */
        protected $table = 'loyalty';

        /**
         * Only the id and order_id are guarded
         *
         * @var array
         */
        protected $guarded = ['id'];


        /**
         * A loyalty record has a user
         *
         * @return void
         */
        public function user()
        {
            return $this->belongsTo( User::class );
        }


    }
