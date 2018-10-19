export interface MfaStatus {
    isMfaEnabled: boolean;
    backupsLastViewed: string | null;
    unusedBackupCount: number | null;
}

export interface MfaSecret {
    secret: string;
    qrCode: string;
}
