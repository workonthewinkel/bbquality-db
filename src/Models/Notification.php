<?php

    namespace BbqData\Models;

    use BbqData\Contracts\Model;

    class Notification extends Model{

        /**
         * Customers table
         *
         * @var string
         */
        protected $table = 'notifications';

        /**
         * Only the id and order_id are guarded
         *
         * @var array
         */
        protected $fillable = [
            'message',
            'data',
            'object_id',
            'type',
        ];

        /**
         * Cast the object field as a json
         *
         * @var array
         */
        protected $casts = [
            'data' => Json::class,
        ];

    }