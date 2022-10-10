<?php 

namespace BbqData\Models\Scopes;


    use Illuminate\Database\Eloquent\Builder;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\Scope;

    class NotCartScope implements Scope {

        /**
         * Apply this scope
         *
         * @param Builder $builder
         * @param Model $model
         * @return void
         */
        public function apply(Builder $builder, Model $model)
        {
            $builder->where([ 'status', '!=', 'cart' ]);
        }

    }
