<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*Enpoints for authorization. Uses Json web tokens (JWT)*/

Route::group(['prefix' => '/auth', ['middleware' => 'throttle:20,2']], function () {
    Route::post('/login', 'Auth\LoginController@login');
});

/*----------------------------------------------------------------------------------------------------------------------------------------------- */
/*Enpoints protected by JWT | Middleware JWT.ATUH checks if request has a valid token*/
/*----------------------------------------------------------------------------------------------------------------------------------------------- */

Route::group(['middleware' => 'jwt.auth'], function () {
    Route::get('/me', 'Auth\MeController@index');
    Route::get('/auth/logout', 'Auth\LogoutController@logout');

    Route::group(['prefix' => '/safe'], function () {
        Route::group(['prefix' => '/users'], function () {
            Route::post('/update', 'UsersController@update');
            Route::post('/update_my_creditors', 'UsersController@updateMyCreditors');
            Route::post('/update_picture', 'UsersController@updateProfilePicture');
            Route::post('/update_news_tags', 'UsersController@updateNewsTags');
        });

        Route::group(['prefix' => '/news'], function () {
        });

        Route::group(['prefix' => '/comments'], function () {
            Route::get('/get_by_user_id/{id}', 'CommentsController@getNewsCommentsByUserId');
            Route::post('/add_for_review', 'CommentsController@addForReview');

            Route::post('/add_for_news', 'CommentsController@addForNews');
            Route::post('/update_for_news/{id}', 'CommentsController@updateForNewsByUser');
        });

        Route::group(['prefix' => '/reviews'], function () {
            Route::get('/get_by_user_id/{id}', 'ReviewsController@getByUserId');
            Route::post('/add_by_slug', 'ReviewsController@addBySlug');
        });
        Route::group(['prefix' => '/requests'], function () {
            Route::post('/change_status_by_id/{id}', 'RequestsController@changeStatusById');
        });
    });
    /*------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- */
    /*Enpoints protected by JWT and double verification with email (keyEmail field should be sent), the verification happens on the middleware EnsureHasPermissions*/
    /*KeyEmail should be an email address of one of the administrators (Roles with access to admin panel), otherwise the middleware throws 403 for all requests*/
    /*------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- */

    Route::group(['middleware' => 'ensureHasPermissions', 'prefix' => '/safex2'], function () {

        /*Enpoints for products*/
        Route::group(['prefix' => '/products'], function () {
            Route::get('/get_all/{xnumber?}', 'Products\ProductsController@getAll');

            /*Enpoints for products->credits*/
            Route::group(['prefix' => '/credits'], function () {
                Route::get('/get_all/{slug?}/{xnumber?}', 'Products\CreditsController@getAllCredits');
                Route::get('/get_by_id/{id}', 'Products\CreditsController@getByIdFull');
                Route::post('/add', 'Products\CreditsController@add');
                Route::post('/update/{id}', 'Products\CreditsController@updateById');
                Route::post('/toggle_active/{id}', 'Products\CreditsController@toggleActiveById');
                Route::post('/toggle_special/{id}', 'Products\CreditsController@toggleSpecialById');
                Route::post('/update_special_priority/{id}', 'Products\CreditsController@updateSpecialPriorityById');
                Route::post('/update_special_link/{id}', 'Products\CreditsController@updateSpecialLinkById');
                Route::post('/delete/{id}', 'Products\CreditsController@deleteById');
                Route::post('/duplicate/{id}', 'Products\CreditsController@duplicateById');
                /*Enpoints for products->credits->consumers*/
                Route::group(['prefix' => '/consumers'], function () {
                    Route::get('/get_all/{xnumber?}', 'Products\CreditsController@getAllConsumers');
                    Route::get('/get_by_id/{id}', 'Products\CreditsController@getByIdFullConsumer');
                    Route::post('/add', 'Products\CreditsController@addConsumer');
                    Route::post('/update/{id}', 'Products\CreditsController@updateConsumerById');
                    Route::post('/toggle_active/{id}', 'Products\CreditsController@toggleActiveByConsumerId');
                    Route::post('/toggle_special/{id}', 'Products\CreditsController@toggleSpecialByConsumerId');
                    Route::post('/update_special_priority/{id}', 'Products\CreditsController@updateSpecialPriorityByConsumerId');
                    Route::post('/update_special_link/{id}', 'Products\CreditsController@updateSpecialLinkByConsumerId');
                    Route::post('/delete/{id}', 'Products\CreditsController@deleteConsumerById');
                    Route::post('/duplicate/{id}', 'Products\CreditsController@duplicateConsumerById');
                });
                /*Enpoints for products->credits->microloans*/
                Route::group(['prefix' => '/microloans'], function () {
                    Route::get('/get_all/{xnumber?}', 'Products\CreditsController@getAllMicroloans');
                    Route::get('/get_by_id/{id}', 'Products\CreditsController@getByIdFullMicroloan');
                    Route::post('/add', 'Products\CreditsController@addMicroloan');
                    Route::post('/update/{id}', 'Products\CreditsController@updateMicroloanById');
                    Route::post('/toggle_active/{id}', 'Products\CreditsController@toggleActiveByMicroloanId');
                    Route::post('/toggle_special/{id}', 'Products\CreditsController@toggleSpecialByMicroloanId');
                    Route::post('/update_special_priority/{id}', 'Products\CreditsController@updateSpecialPriorityByMicroloanId');
                    Route::post('/update_special_link/{id}', 'Products\CreditsController@updateSpecialLinkByMicroloanId');
                    Route::post('/delete/{id}', 'Products\CreditsController@deleteMicroloanById');
                    Route::post('/duplicate/{id}', 'Products\CreditsController@duplicateMicroloanById');
                });
            });
            /*Enpoints for products->deposits*/
            Route::group(['prefix' => '/deposits'], function () {
                Route::get('/get_all/{xnumber?}', 'Products\DepositsController@getAll');
                Route::get('/get_by_id/{id}', 'Products\DepositsController@getByIdFull');
                Route::post('/add', 'Products\DepositsController@add');
                Route::post('/update/{id}', 'Products\DepositsController@updateById');
                Route::post('/toggle_active/{id}', 'Products\DepositsController@toggleActiveById');
                Route::post('/toggle_special/{id}', 'Products\DepositsController@toggleSpecialById');
                Route::post('/update_special_priority/{id}', 'Products\DepositsController@updateSpecialPriorityById');
                Route::post('/update_special_link/{id}', 'Products\DepositsController@updateSpecialLinkById');
                Route::post('/delete/{id}', 'Products\DepositsController@deleteById');
                Route::post('/duplicate/{id}', 'Products\DepositsController@duplicateById');
            });
            /*Enpoints for products->cards*/
            Route::group(['prefix' => '/cards'], function () {
                Route::get('/get_all/{xnumber?}', 'Products\CardsController@getAll');
                Route::get('/get_by_id/{id}', 'Products\CardsController@getByIdFull');
                Route::post('/add', 'Products\CardsController@add');
                Route::post('/update/{id}', 'Products\CardsController@updateById');
                Route::post('/toggle_active/{id}', 'Products\CardsController@toggleActiveById');
                Route::post('/toggle_special/{id}', 'Products\CardsController@toggleSpecialById');
                Route::post('/update_special_priority/{id}', 'Products\CardsController@updateSpecialPriorityById');
                Route::post('/update_special_link/{id}', 'Products\CardsController@updateSpecialLinkById');
                Route::post('/delete/{id}', 'Products\CardsController@deleteById');
                Route::post('/duplicate/{id}', 'Products\CardsController@duplicateById');
            });
        });

        /*Enpoints for news*/
        Route::group(['prefix' => '/news'], function () {
            Route::get('/get_all/{xnumber?}', 'NewsController@getAll');
            Route::get('/get_by_id/{id}', 'NewsController@getByIdFullAdmin');
            Route::post('/add', 'NewsController@add');
            Route::post('/update/{id}', 'NewsController@updateById');
            Route::post('/toggle_active/{id}', 'NewsController@toggleActiveById');
            Route::post('/delete/{id}', 'NewsController@deleteById');
            /*Enpoints for news->comments*/
            Route::group(['prefix' => '/comments'], function () {
                Route::get('/get_all/{xnumber?}', 'CommentsController@getAllForNews');
                Route::get('/get_by_id/{id}', 'CommentsController@getByIdFullForNews');
                Route::post('/add', 'CommentsController@addForNewsByAdmin');
                Route::post('/update/{id}', 'CommentsController@updateForNews');
                Route::post('/toggle_active/{id}', 'CommentsController@toggleActiveByIdForNews');
                Route::post('/delete/{id}', 'CommentsController@deleteByIdForNews');
                Route::get('/get_count_unmoderated', 'CommentsController@getCountUnmoderatedForNews');
            });
        });

        /*Enpoints for reviews*/
        Route::group(['prefix' => '/reviews'], function () {
            Route::get('/get_all/{xnumber?}', 'ReviewsController@getAll');
            Route::get('/get_by_id/{id}', 'ReviewsController@getByIdFull');
            Route::post('/add', 'ReviewsController@add');
            Route::post('/update/{id}', 'ReviewsController@updateById');
            Route::post('/toggle_active/{id}', 'ReviewsController@toggleActiveById');
            Route::post('/delete/{id}', 'ReviewsController@deleteById');
            Route::get('/get_count_unmoderated', 'ReviewsController@getCountUnmoderated');

            /*Enpoints for reviews->comments*/
            Route::group(['prefix' => '/comments'], function () {
                Route::get('/get_all/{xnumber?}', 'CommentsController@getAllForReviews');
                Route::get('/get_by_id/{id}', 'CommentsController@getByIdFullForReviews');
                Route::post('/add', 'CommentsController@addForReviewsByAdmin');
                Route::post('/update/{id}', 'CommentsController@updateForReviews');
                Route::post('/toggle_active/{id}', 'CommentsController@toggleActiveByIdForReviews');
                Route::post('/delete/{id}', 'CommentsController@deleteByIdForReviews');
                Route::get('/get_count_unmoderated', 'CommentsController@getCountUnmoderatedForReviews');
            });
        });

        /*Enpoints for creditors*/
        Route::group(['prefix' => '/creditors'], function () {
            Route::get('/get_all/{xnumber?}', 'CreditorsController@getAll');
            Route::get('/get_by_id/{id}', 'CreditorsController@getByIdFull');
            Route::post('/add', 'CreditorsController@add');
            Route::post('/update/{id}', 'CreditorsController@updateById');
            Route::post('/toggle_active/{id}', 'CreditorsController@toggleActiveById');
            Route::post('/delete/{id}', 'CreditorsController@deleteById');
            /*Enpoints for blacklist*/
            Route::group(['prefix' => '/blacklist'], function () {
                Route::get('/get_all/{xnumber?}', 'CreditorsController@getAllInBlackList');
                Route::get('/get_by_id/{id}', 'CreditorsController@getBlackListByIdFull');
                Route::post('/update/{id}', 'CreditorsController@updateBlackListById');
                Route::post('/toggle_active/{id}', 'CreditorsController@toggleActiveBlackListById');
                Route::post('/delete/{id}', 'CreditorsController@deleteBlackListById');
            });
        });

        /*Enpoints for SEO*/
        Route::group(['prefix' => '/seo'], function () {
            Route::get('/get_all/{xnumber?}', 'SeoController@getAll');
            Route::get('/get_by_id/{id}', 'SeoController@getByIdFull');
            Route::post('/add', 'SeoController@add');
            Route::post('/update/{id}', 'SeoController@updateById');
            Route::post('/toggle_active/{id}', 'SeoController@toggleActiveById');
            Route::post('/delete/{id}', 'SeoController@deleteById');
            /*Enpoints for regions*/
            Route::group(['prefix' => '/regions'], function () {
                Route::get('/get_all/{xnumber?}', 'RegionsController@getAll');
                Route::get('/get_by_id/{id}', 'RegionsController@getByIdFull');
                Route::post('/add', 'RegionsController@add');
                Route::post('/update/{id}', 'RegionsController@updateById');
                Route::post('/toggle_active/{id}', 'RegionsController@toggleActiveById');
                Route::post('/delete/{id}', 'RegionsController@deleteById');
            });
            /*Enpoints for ready queries (Готовые запросы/Готовые решения)*/
            Route::group(['prefix' => '/queries'], function () {
                Route::get('/get_all/{xnumber?}', 'SeoController@getAllQueries');
                Route::get('/get_by_id/{id}', 'SeoController@getByIdFullQuery');
                Route::post('/add', 'SeoController@addQuery');
                Route::post('/update/{id}', 'SeoController@updateByQueryId');
                Route::post('/toggle_active/{id}', 'SeoController@toggleActiveByQueryId');
                Route::post('/delete/{id}', 'SeoController@deleteByQueryId');
            });
        });

        /*Enpoints for requests*/
        Route::group(['prefix' => '/requests'], function () {
            /*Enpoints for credits requests*/
            Route::group(['prefix' => '/credits'], function () {
                Route::get('/get_all/{xnumber?}', 'RequestsController@getAllCreditRequests');
                Route::get('/get_by_id/{id}', 'RequestsController@getCreditRequestByIdFull');
                Route::post('/add', 'RequestsController@addCreditRequest');
                Route::post('/update/{id}', 'RequestsController@updateCreditRequestById');
                Route::post('/delete/{id}', 'RequestsController@deleteCreditRequestById');
            });
            /*Enpoints for cards requests*/
            Route::group(['prefix' => '/cards'], function () {
                Route::get('/get_all/{xnumber?}', 'RequestsController@getAllCardRequests');
                Route::get('/get_by_id/{id}', 'RequestsController@getCardRequestByIdFull');
                Route::post('/add', 'RequestsController@addCardRequest');
                Route::post('/update/{id}', 'RequestsController@updateCardRequestById');
                Route::post('/delete/{id}', 'RequestsController@deleteCardRequestById');
            });
            /*Enpoints for deposits requests*/
            Route::group(['prefix' => '/deposits'], function () {
                Route::get('/get_all/{xnumber?}', 'RequestsController@getAllDepositRequests');
                Route::get('/get_by_id/{id}', 'RequestsController@getDepositRequestByIdFull');
                Route::post('/add', 'RequestsController@addDepositRequest');
                Route::post('/update/{id}', 'RequestsController@updateDepositRequestById');
                Route::post('/delete/{id}', 'RequestsController@deleteDepositRequestById');
            });

            /*Enpoints for call requests*/
            Route::group(['prefix' => '/calls'], function () {
                Route::get('/get_all/{xnumber?}', 'RequestsController@getAllCallRequests');
                Route::get('/get_by_id/{id}', 'RequestsController@getCallRequestByIdFull');
                Route::post('/add', 'RequestsController@addCallRequestAdmin');
                Route::post('/update/{id}', 'RequestsController@updateCallRequestById');
                Route::post('/delete/{id}', 'RequestsController@deleteCallRequestById');
            });
        });

        /*Enpoints for roles*/
        Route::group(['prefix' => '/roles'], function () {
            Route::get('/get_all/{xnumber?}', 'RolesController@getAll');
            Route::get('/get_by_id/{id}', 'RolesController@getByIdFull');
            Route::post('/add', 'RolesController@add');
            Route::post('/update/{id}', 'RolesController@updateById');
            Route::post('/toggle_active/{id}', 'RolesController@toggleActiveById');
            Route::post('/delete/{id}', 'RolesController@deleteById');
        });

        /*Enpoints for currencies*/
        Route::group(['prefix' => '/currencies'], function () {
            Route::get('/get_all/{xnumber?}', 'CurrenciesController@getAll');
            Route::get('/get_by_id/{id}', 'CurrenciesController@getByIdFull');
            Route::post('/add', 'CurrenciesController@add');
            Route::post('/update/{id}', 'CurrenciesController@updateById');
            Route::post('/toggle_active/{id}', 'CurrenciesController@toggleActiveById');
            Route::post('/delete/{id}', 'CurrenciesController@deleteById');
            Route::group(['prefix' => '/courses'], function () {
                /*Enpoints for creditors exchange rates*/
                Route::group(['prefix' => '/creditors'], function () {
                    Route::get('/get_all/{xnumber?}', 'CurrenciesController@getAllCreditorExchangeRates');
                    Route::get('/get_by_id/{id}', 'CurrenciesController@getCreditorExchangeRateByIdFull');
                    Route::post('/add', 'CurrenciesController@addCreditorExchangeRate');
                    Route::post('/update/{id}', 'CurrenciesController@updateCreditorExchangeRateById');
                    // Route::post('/toggle_active/{id}', 'CurrenciesController@toggleActiveById');
                    Route::post('/delete/{id}', 'CurrenciesController@deleteCreditorExchangeRateById');
                });
            });
        });

        /*Enpoints for users*/
        Route::group(['prefix' => '/users'], function () {
            Route::get('/get_all/{xnumber?}', 'UsersController@getAll');
            Route::get('/get_by_id/{id}', 'UsersController@getByIdFull');
            Route::post('/add', 'UsersController@add');
            Route::post('/update/{id}', 'UsersController@updateById');
            Route::post('/delete/{id}', 'UsersController@deleteById');
        });

        /*Enpoints for logs*/
        Route::group(['prefix' => '/logs'], function () {
            Route::group(['prefix' => '/internals'], function () {
                Route::get('/get_all/{xnumber?}', 'LogsController@getAllInternalLogs');
                Route::get('/get_by_id/{id}', 'LogsController@getInternalLogById');
            });
            Route::group(['prefix' => '/externals'], function () {
                Route::get('/get_all', 'LogsController@getAllExternalLogs');
            });
        });
    });
});


/*----------------------------------------------------------------------------------------------------------------------------------------------- */
/* Routes unprotected by JWT and double verification with email */
/*----------------------------------------------------------------------------------------------------------------------------------------------- */

/*Enpoints for users (Unprotected | No JWT verification)*/
Route::group(['prefix' => '/users'], function () {
    Route::post('/validate', 'UsersController@validateDataAndSendSms');
    Route::post('/reset_code', 'UsersController@resetCodeAndSendSms');
    Route::post('/create_user', 'UsersController@createUser');
    Route::post('/validate_phone', 'UsersController@validatePhoneAndSendSms');
    Route::post('/reset_password', 'UsersController@resetPassword');
    Route::get('/get_all/{xnumber?}', 'UsersController@getUsers');
});

/*Enpoints for news (Unprotected | No JWT verification)*/
Route::group(['prefix' => '/news'], function () {
    /* String slug: day, all, week, analitycs, comparisons, advices, press (See all on the table news_themes). If slug is not send it will give all the news (paginated or not depending on the request) | Int Xnumber: whatever integer. If not send it will get all news without a limit */
    Route::get('/get_xnumber_by_slug/{slug}/{xnumber?}', 'NewsController@getXnumberBySlug');

    /*  Method returns news by advice slug.   | String slug:  deposits, credits, mortgage, services, investments | If slug is not send it will give all the news (paginated or not depending on the request) | Int Xnumber: whatever integer. If not send it will get all news without a limit  */
    Route::get('/get_xnumber_by_advice_slug/{slug}/{xnumber?}', 'NewsController@getXnumberByAdviceSlug');

    /* String ID:  News identificator */
    Route::get('/get_by_id/{id}', 'NewsController@getById');

    /* String ID:  News identificator */
    Route::get('/get_by_id_full/{id}', 'NewsController@getByIdFull');

    /* Getting the data for the index page on the Front-end. News divided by categories  https://bankiroff.ru/news */
    Route::get('/get_data_index', 'NewsController@getForIndexPage');

    /* Getting news themes | Has option for KeyValues */
    Route::get('/get_themes', 'NewsController@getThemes');

    /* Getting advices slugs  | Has option for KeyValues*/
    Route::get('/get_advices_slugs', 'NewsController@getAdvicesSlugs');

    /* Getting  tags  | Has option for KeyValues*/
    Route::get('/get_tags', 'NewsController@getTags');

    /* Getting news as KeyValue */
    Route::get('/get_all/{xnumber?}', 'NewsController@getAllAsKeyValue');
});

/*Enpoints for creditors (Unprotected | No JWT verification)*/
Route::group(['prefix' => '/creditors'], function () {
    /* Method returns creditors | String slug: banks, mfo, pawnshops, brokers  (See all on the table creditors_types). If slug is not send it will give all the creditors (paginated or not depending on the request) | Int Xnumber: whatever integer. If not send it will get all creditors without a limit */
    Route::get('/get_xnumber_with_products_by_slug/{slug?}/{xnumber?}', 'CreditorsController@getAllWithProductsByProductSlug');
    
    /* Method returns creditors | String slug: banks, mfo, pawnshops, brokers  (See all on the table creditors_types). If slug is not send it will give all the creditors (paginated or not depending on the request) | Int Xnumber: whatever integer. If not send it will get all creditors without a limit */
    Route::get('/get_xnumber_by_slug/{slug?}/{xnumber?}', 'CreditorsController@getXnumberBySlug');

    /* Method returns creditors (paginated or not depending on the request) | Int Xnumber: whatever integer. If not send it will get all creditors without a limit*/
    Route::get('/get_all/{xnumber?}', 'CreditorsController@getAllByXnumber');

    /* Method returns creditors (paginated or not depending on the request) | Int Xnumber: whatever integer. If not send it will get all creditors without a limit*/
    Route::get('/get_all_with_regions/{xnumber?}', 'CreditorsController@getAllActiveWithRegions');

    /* String ID:  Creditor identificator */
    Route::get('/get_by_id/{id}', 'CreditorsController@getById');

    /* ROUTES FOR RATINGS PAGE */
    Route::get('/get_by_rating_slug/{slug}/{xnumber?}', 'CreditorsController@getByRatingSlugByXnumber');

    /* Method returns TOP creditors | Int Xnumber: whatever integer */
    Route::get('/get_top_xnumber/{xnumber?}', 'CreditorsController@getTopXnumber');

    Route::post('/request_registration', 'CreditorsController@requestRegistration');

    Route::get('/get_creditors_blacklist/{xnumber?}', 'CreditorsController@getCreditorsInBlackList');
});

/*Enpoints for reviews (Unprotected | No JWT verification)*/
Route::group(['prefix' => '/reviews'], function () {

    /* Method returns reviews paginated for an specific creditor (creditor, etc) | Int Xnumber: whatever integer CHECK IF USED*/
    Route::get('/get_by_creditor_id/{id}/{xnumber?}/', 'ReviewsController@getByCreditorID');

    /* Method returns reviews for an specific slug and id | Int Xnumber: whatever integer */
    Route::get('/get_by_slug_and_id/{slug}/{id}/{xnumber?}/', 'ReviewsController@getXnumberBySlugAndID');

    /* String slug | Int Xnumber: whatever integer */
    Route::get('/get_xnumber_by_slug/{slug}/{xnumber?}', 'ReviewsController@getXnumberBySlug');

    /* String slug | Int Xnumber: whatever integer getXnumberBySlugAndProductSlug*/
    Route::get('/get_xnumber_by_product_slug/{slug}/{xnumber?}', 'ReviewsController@getXnumberByProductSlug');

    /* String slug: banks, mfo, brokers, pawnshops | Int Xnumber: whatever integer */
    Route::get('/get_xnumber_by_creditor_slug/{slug}/{xnumber?}', 'ReviewsController@getXnumberByCreditorSlug');

    /* String ID:  Review identificator */
    Route::get('/get_by_id/{id}', 'ReviewsController@getById');

    /* Getting reviews as KeyValue */
    Route::get('/get_all/{xnumber?}', 'ReviewsController@getAllAsKeyValue');
});


/*Enpoints for all products (deposits, cards, consumers, credits, etc) (Unprotected | No JWT verification)*/
Route::group(['prefix' => '/products'], function () {

    /* Commom endpoints for all products*/
    Route::get('/get_by_slug_and_creditor_id/{slug}/{creditorId}/{xnumber}/', 'Products\ProductsController@getBySlugAndCreditorID');
    Route::get('/get_by_slug_and_creditor_id_extra/{slug}/{creditorId}/{xnumber}/', 'Products\ProductsController@getBySlugAndCreditorIDExtra');
    Route::get('/get_by_slug/{slug}/{xnumber}/', 'Products\ProductsController@getBySlug');
    Route::get('/get_marketing/{slug}', 'Products\ProductsController@getMarketingProducts');
    Route::get('/get_populars_by_slug/{slug}/{xnumber}/', 'Products\ProductsController@getPopularsBySlug');
    
    /* Endpoints for special products (products for Лучшее от банков and the section Акции и специальные предложения)*/
    Route::group(['prefix' => '/special'], function () {
        Route::get('/get_by_slug/{slug}/{xnumber?}/', 'Products\ProductsController@getSpecialBySlug');
        Route::get('/get_all/{xnumber?}/', 'Products\ProductsController@getAllSpecial');
        Route::get('/get_tabs', 'Products\ProductsController@getSpecialTabs');
    });


    /* Endpoints for cards*/
    Route::group(['prefix' => '/cards'], function () {
        Route::get('/get_bonuses', 'Products\CardsController@getBonuses');
        Route::get('/get_categories', 'Products\CardsController@getCategories');
        Route::get('/get_options_by_slug/{slug?}', 'Products\CardsController@getOptionsBySlug');
        Route::get('/get_by_id/{id}', 'Products\CardsController@getById');
        Route::get('/get_type_by_id/{id}', 'Products\CardsController@getCardTypeById');
        Route::get('/get_min_max_by_slug/{slug}', 'Products\CardsController@getMinMaxBySlug');

    });

    /* Endpoints for deposits*/
    Route::group(['prefix' => '/deposits'], function () {
        Route::get('/get_types', 'Products\DepositsController@getTypes');
        Route::get('/get_by_id/{id}', 'Products\DepositsController@getById');
        Route::get('/get_capitalizations', 'Products\DepositsController@getCapitalizations');
        Route::get('/get_interest_payments', 'Products\DepositsController@getInterestPayments');
        Route::get('/get_min_max', 'Products\DepositsController@getMinMax');

    });
    /* Endpoints for credits (all of them less microloans and consumers)*/
    Route::group(['prefix' => '/credits'], function () {
        Route::get('/get_by_slug_and_id/{slug}/{id}', 'Products\CreditsController@getBySlugAndID');
        Route::get('/get_by_creditor_slug/{slug?}/{xnumber?}', 'Products\CreditsController@getByCreditorSlug');
        Route::get('/get_best_percent_and_average/{slug?}', 'Products\CreditsController@getBestPercentAndAverage');
        Route::get('/get_min_percent_by_slug/{slug}', 'Products\CreditsController@getMinPercentBySlug');    
        Route::get('/get_min_max_by_slug/{slug}', 'Products\CreditsController@getMinMaxBySlug');
        Route::get('/get_histories', 'Products\CreditsController@getCreditsHistories');
        Route::get('/get_proofs', 'Products\CreditsController@getCreditsProofs');
        Route::get('/get_occupations', 'Products\CreditsController@getCreditsOccupations');
        Route::get('/get_pledge_slugs', 'Products\CreditsController@getCreditsPledgeSlugs');
        Route::get('/get_insurances', 'Products\CreditsController@getCreditsInsurances');

        /* Endpoints for consumers*/
        Route::group(['prefix' => '/consumers'], function () {
            Route::get('/get_purposes', 'Products\CreditsController@getConsumersPurposes');
        });

        /* Endpoints for microloans*/
        Route::group(['prefix' => '/microloans'], function () {
            Route::get('/get_purposes', 'Products\CreditsController@getMicroloansPurposes');
            Route::get('/get_provisions', 'Products\CreditsController@getMicroloansProvisions');
        });
    });
});

/*Enpoints for currencies (Unprotected | No JWT verification)*/
Route::group(['prefix' => '/currencies'], function () {
    Route::get('/get_currency_filters', 'CurrenciesController@getFilters');
    Route::group(['prefix' => '/courses'], function () {
        Route::get('/get_by_code/{code}', 'CurrenciesController@getByCode');
        Route::get('/get_by_date/{dd?}/{mm?}/{yyyy?}', 'CurrenciesController@getAllByDate');
        Route::get('/convert/{sum?}/{currency_from?}/{currency_to?}/{currrency_base?}', 'CurrenciesController@convert');
    });
});


/*Enpoints for requests (Unprotected | No JWT verification)*/
Route::group(['prefix' => '/requests'], function () {
    Route::post('/add_support', 'RequestsController@addSupportRequest');
    Route::post('/add_history', 'RequestsController@addCheckCreditHistoryRequest');
    Route::post('/add_creditor_blacklist', 'RequestsController@addBlackListRequest');
    Route::post('/add_call', 'RequestsController@addCallRequest');
    Route::post('/add_product', 'RequestsController@addProductRequest');
    Route::post('/add_credit_selection', 'RequestsController@addCreditSelectionRequest');
    Route::post('/add_advertisement', 'RequestsController@addAdvertisementRequest');
});

/*Enpoints for requests (Unprotected | No JWT verification)*/
Route::group(['prefix' => '/advertisements'], function () {
    Route::get('/get_types', 'AdvertisementsController@getTypes');
});

/*Enpoints for regions (Unprotected | No JWT verification)*/
Route::group(['prefix' => '/regions'], function () {
    Route::get('/get_regions', 'RegionsController@getRegions');
});

/*Enpoints for search (Unprotected | No JWT verification)*/
Route::group(['prefix' => '/search'], function () {
    Route::get('/get_results/{xnumber?}', 'SearchController@search');
    Route::get('/get_products_and_creditors/{xnumber?}', 'SearchController@searchProductsAndCreditorsAsKeyValue');
});

/*Enpoints for seo (Unprotected | No JWT verification)*/
Route::group(['prefix' => '/seo'], function () {
    /* String subdomain: msk,spb,etc | Additional: URL is sent by params ?url=my/custom/pageurl */
    Route::get('/get_all_variables', 'SeoController@getAllVariables');
    Route::get('/get_by_url', 'SeoController@getSeoByUrl');
    Route::get('/get_about_pages', 'SeoController@getAboutPages');

    Route::group(['prefix' => '/queries'], function () {
        Route::get('/get_by_url', 'SeoController@getReadyQueriesByUrl');
        Route::get('/get_display_pages', 'SeoController@getReadyQueryDisplayPages');
        Route::get('/get_divisions', 'SeoController@getReadyQueryDivisions');
    });
});

/*Enpoints for sitemaps (Unprotected | No JWT verification)*/
Route::group(['prefix' => '/sitemaps'], function () {
    Route::get('/index', 'SitemapsController@getIndex');
    Route::get('/{type}_{number?}', 'SitemapsController@getLinksBySitemapTypeAndNumber');
});

/*Enpoints for RSS (Unprotected | No JWT verification)*/
Route::group(['prefix' => '/rss'], function () {
    Route::get('/get_by_params/{type}/{xDays}', 'RSSController@getRSSByParams');
});

/*Enpoints for Subdomains*/
Route::group(['prefix' => '/subdomains'], function () {
    Route::get('/get_by_string/{string?}', 'SubdomainsController@getByStr');
    Route::get('/get_data/{string?}', 'SubdomainsController@getDataByStr');
    Route::get('/get_all_active', 'SubdomainsController@getAllActive');
});

/*Enpoints for addresses internal system (Unprotected | No JWT verification)*/
Route::group(['prefix' => '/addresses'], function () {
    Route::get('/get_areas/{xnumber?}', 'AddressesController@getAreas');
    Route::get('/get_settlements/{xnumber?}', 'AddressesController@getSettlements');
});

/*Enpoints for roles (Unprotected | No JWT verification)*/
Route::group(['prefix' => '/roles'], function () {
    Route::get('/get_roles', 'RolesController@getRoles');
});

/*Enpoints for redirects (Unprotected | No JWT verification)*/
Route::group(['prefix' => '/redirects'], function () {
    Route::get('/get_by_url', 'RedirectsController@getRedirectByUrl');
});
