<?php

namespace Modules\Store\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Store\Models\Category;
use Illuminate\Support\Str;

class StoreCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Define main categories with their subcategories
        $categories = [
            [
                'name' => 'Mobile Phones & Accessories',
                'slug' => 'mobile-phones-accessories',
                'description' => 'Smartphones, mobile accessories, and related products',
                'subcategories' => [
                    [
                        'name' => 'Smartphones',
                        'slug' => 'smartphones',
                        'description' => 'Latest smartphones from top brands'
                    ],
                    [
                        'name' => 'Mobile Accessories',
                        'slug' => 'mobile-accessories',
                        'description' => 'Cases, chargers, screen protectors, and more',
                        'subcategories' => [
                            [
                                'name' => 'Phone Cases & Covers',
                                'slug' => 'phone-cases-covers',
                                'description' => 'Protective cases and covers for smartphones'
                            ],
                            [
                                'name' => 'Chargers & Cables',
                                'slug' => 'chargers-cables',
                                'description' => 'Charging cables, adapters, and wireless chargers'
                            ],
                            [
                                'name' => 'Screen Protectors',
                                'slug' => 'screen-protectors',
                                'description' => 'Tempered glass and film screen protectors'
                            ],
                            [
                                'name' => 'Power Banks',
                                'slug' => 'power-banks',
                                'description' => 'Portable chargers and power banks'
                            ]
                        ]
                    ],
                    [
                        'name' => 'Smartwatches',
                        'slug' => 'smartwatches',
                        'description' => 'Smart watches and fitness trackers'
                    ]
                ]
            ],
            [
                'name' => 'Home Appliances',
                'slug' => 'home-appliances',
                'description' => 'Essential home appliances for modern living',
                'subcategories' => [
                    [
                        'name' => 'Kitchen Appliances',
                        'slug' => 'kitchen-appliances',
                        'description' => 'Appliances for modern kitchens',
                        'subcategories' => [
                            [
                                'name' => 'Refrigerators',
                                'slug' => 'refrigerators',
                                'description' => 'Single door, double door, and side-by-side refrigerators'
                            ],
                            [
                                'name' => 'Ovens & Microwaves',
                                'slug' => 'ovens-microwaves',
                                'description' => 'Microwave ovens, toaster ovens, and conventional ovens'
                            ],
                            [
                                'name' => 'Small Kitchen Appliances',
                                'slug' => 'small-kitchen-appliances',
                                'description' => 'Toasters, blenders, food processors, and more',
                                'subcategories' => [
                                    [
                                        'name' => 'Toasters & Grills',
                                        'slug' => 'toasters-grills',
                                        'description' => 'Toasters, sandwich makers, and grills'
                                    ],
                                    [
                                        'name' => 'Blenders & Mixers',
                                        'slug' => 'blenders-mixers',
                                        'description' => 'Blenders, food processors, and hand mixers'
                                    ],
                                    [
                                        'name' => 'Coffee Makers',
                                        'slug' => 'coffee-makers',
                                        'description' => 'Coffee machines and makers'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'name' => 'Air Conditioners',
                        'slug' => 'air-conditioners',
                        'description' => 'Air conditioning units for home and office',
                        'subcategories' => [
                            [
                                'name' => 'Split ACs',
                                'slug' => 'split-acs',
                                'description' => 'Split air conditioners for home and office'
                            ],
                            [
                                'name' => 'Window ACs',
                                'slug' => 'window-acs',
                                'description' => 'Window air conditioners'
                            ],
                            [
                                'name' => 'Portable ACs',
                                'slug' => 'portable-acs',
                                'description' => 'Portable air conditioners'
                            ]
                        ]
                    ],
                    [
                        'name' => 'Washing Machines',
                        'slug' => 'washing-machines',
                        'description' => 'Washing machines and dryers',
                        'subcategories' => [
                            [
                                'name' => 'Front Load',
                                'slug' => 'front-load-washers',
                                'description' => 'Front loading washing machines'
                            ],
                            [
                                'name' => 'Top Load',
                                'slug' => 'top-load-washers',
                                'description' => 'Top loading washing machines'
                            ],
                            [
                                'name' => 'Washer Dryers',
                                'slug' => 'washer-dryers',
                                'description' => 'Combined washer and dryer units'
                            ]
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Computers & Laptops',
                'slug' => 'computers-laptops',
                'description' => 'Computers, laptops, and accessories',
                'subcategories' => [
                    [
                        'name' => 'Laptops',
                        'slug' => 'laptops',
                        'description' => 'Notebooks and laptops for work and gaming'
                    ],
                    [
                        'name' => 'Desktop Computers',
                        'slug' => 'desktop-computers',
                        'description' => 'Desktop PCs and all-in-one computers'
                    ],
                    [
                        'name' => 'Computer Accessories',
                        'slug' => 'computer-accessories',
                        'description' => 'Keyboards, mice, monitors, and more',
                        'subcategories' => [
                            [
                                'name' => 'Monitors',
                                'slug' => 'monitors',
                                'description' => 'Computer monitors and displays'
                            ],
                            [
                                'name' => 'Keyboards & Mice',
                                'slug' => 'keyboards-mice',
                                'description' => 'Computer keyboards and mice'
                            ],
                            [
                                'name' => 'Storage Devices',
                                'slug' => 'storage-devices',
                                'description' => 'External hard drives and SSDs'
                            ]
                        ]
                    ]
                ]
            ],
            [
                'name' => 'TVs & Entertainment',
                'slug' => 'tvs-entertainment',
                'description' => 'Televisions and home entertainment systems',
                'subcategories' => [
                    [
                        'name' => 'Televisions',
                        'slug' => 'televisions',
                        'description' => 'Smart TVs and LED TVs',
                        'subcategories' => [
                            [
                                'name' => 'Smart TVs',
                                'slug' => 'smart-tvs',
                                'description' => 'Internet-enabled smart televisions'
                            ],
                            [
                                'name' => 'LED TVs',
                                'slug' => 'led-tvs',
                                'description' => 'LED and LCD televisions'
                            ]
                        ]
                    ],
                    [
                        'name' => 'Audio Systems',
                        'slug' => 'audio-systems',
                        'description' => 'Home theater and audio systems',
                        'subcategories' => [
                            [
                                'name' => 'Home Theater Systems',
                                'slug' => 'home-theater-systems',
                                'description' => 'Complete home theater systems'
                            ],
                            [
                                'name' => 'Soundbars',
                                'slug' => 'soundbars',
                                'description' => 'Sound bars and speakers'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        // Create categories recursively
        foreach ($categories as $categoryData) {
            $this->createCategory($categoryData);
        }
    }

    /**
     * Create a category and its subcategories recursively
     */
    protected function createCategory(array $data, ?Category $parent = null): Category
    {
        // Create the category
        $category = Category::updateOrCreate(
            [
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'],
                'parent_id' => $parent?->id,
            ],
            [
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'],
                'parent_id' => $parent?->id,
            ]
        );

        // Create subcategories if any
        if (isset($data['subcategories'])) {
            foreach ($data['subcategories'] as $subcategoryData) {
                $this->createCategory($subcategoryData, $category);
            }
        }

        return $category;
    }
}
