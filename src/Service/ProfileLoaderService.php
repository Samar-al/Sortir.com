<?php

namespace App\Service;

use League\Csv\Exception;
use League\Csv\InvalidArgument;
use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\SyntaxError;
use League\Csv\UnavailableStream;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProfileLoaderService
{
    private SluggerInterface $slugger;
    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public function changeFileName($file): String {

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $safeFilename = $this->slugger->slug($originalFilename);

       return $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
    }

    /**
     * @throws UnavailableStream
     * @throws InvalidArgument
     * @throws SyntaxError
     * @throws Exception
     */
    public function loadData($filePath) {

        $reader = Reader::createFromPath($filePath, 'r');

//        $reader->setHeaderOffset(0);

        // Lire les enregistrements
        $stmt = (new Statement());
        return $stmt->process($reader);
    }


}