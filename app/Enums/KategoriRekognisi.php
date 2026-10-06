<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum KategoriRekognisi: string implements HasLabel
{
    case Penghargaan = 'penghargaan';
    case KeynoteSpeaker = 'keynote_speaker';
    case VisitingLecturer = 'visiting_lecturer';
    case EditorReviewerJurnal = 'editor_reviewer_jurnal';
    case TenagaAhli = 'tenaga_ahli';
    case Lainnya = 'lainnya';

    public function getLabel(): string
    {
        return match ($this) {
            self::Penghargaan => 'Penghargaan',
            self::KeynoteSpeaker => 'Keynote speaker',
            self::VisitingLecturer => 'Visiting lecturer',
            self::EditorReviewerJurnal => 'Editor/reviewer jurnal',
            self::TenagaAhli => 'Tenaga ahli',
            self::Lainnya => 'Lainnya',
        };
    }
}
