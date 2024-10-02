<?php

namespace App\UseCases;
use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Collections\LinksCollection;
use AmoCRM\Collections\TagsCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Models\BaseApiModel;
use AmoCRM\Models\ContactModel;
use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\TextCustomFieldValueModel;
use AmoCRM\Models\LeadModel;
use AmoCRM\Models\NoteModel;
use AmoCRM\Models\NoteType\CommonNote;
use AmoCRM\Models\TagModel;
use AmoCRM\OAuth2\Client\Provider\AmoCRMException;
use Exception;
use League\OAuth2\Client\Token\AccessToken;


class AmoService
{
    public static function getClient()
    {
        $redirectUri = 'https://stat.mzpo-s.ru/main/';
        $apiClient = new AmoCRMApiClient(env('AMO_ID'), env('AMO_SECRET'), $redirectUri);
        $at = new AccessToken([
            'access_token' => env('AMO_TOKEN'),
            'baseDomain' => env('AMO_SUBDOMAIN'),
            'expires' => \Carbon\Carbon::parse('30.08.2027')->timestamp
        ]);
        $apiClient->setAccessToken($at)
            ->setAccountBaseDomain($at->getValues()['baseDomain'].'.amocrm.ru');
        return $apiClient;
    }
}
