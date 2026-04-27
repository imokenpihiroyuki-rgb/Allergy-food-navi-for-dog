<?php

namespace AFN;

if (! defined('ABSPATH')) {
    exit;
}

final class Config
{
    public const POST_TYPE = 'ryouhou_food';
    public const PAGE_SLUG = 'allergy-food-navi-for-dog';

    public const KEY_PROTEIN = 'protein';
    public const KEY_OTHER_ING = 'OtherIngredients';
    public const KEY_HYDROLYZED = 'Hydrolyzed_protein';
    public const KEY_MAKER = 'maker';
    public const KEY_HYDRO_FLAG = 'hydro_flag';

    public const MAKER_ORDER = [
        'ロイヤルカナン',
        'ヒルズ',
        'ペットライン',
        'ピュリナ',
        'ラボライン',
        'ファルミナ',
        'ビルバック',
        '森乳サンワールド',
        'ベッツソリューション',
        'その他のメーカー',
    ];

    public const QUICK_EDIT_PACK_FIELD_KEY = 'field_68d0095bc5210';
}
