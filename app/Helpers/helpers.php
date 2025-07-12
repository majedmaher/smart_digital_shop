<?php

if (!function_exists('saveImage')) {
    function saveImage($photo, $folder): string
    {
        $image = $photo->getClientOriginalName(); //Name with extension 'filename.jpg'
        $name = explode('.', $image)[0]; // Filename 'filename'

        $fileName = $name . uniqid() . '.' . $photo->getClientOriginalExtension();
        $photo->move(public_path($folder), $fileName);
        return $folder . '/' . $fileName;
    }
}

if (!function_exists('saveImageInStorage')) {
    function saveImageInStorage($photo, $folder): string
    {
        $image = $photo->getClientOriginalName(); //Name with extension 'filename.jpg'
        $name = explode('.', $image)[0]; // Filename 'filename'

        $fileName = $name . uniqid() . '.' . $photo->getClientOriginalExtension();
        $photo->storeAs($folder, $fileName, 'uploads');
        return 'uploads/' . $folder . '/' . $fileName;
    }
}
