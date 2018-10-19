import {Injectable} from '@angular/core';
import {MfaSecret, MfaStatus} from "../models/MFA";
import {ApiService} from "./ApiService";

@Injectable({
    providedIn: 'root',
})
export class MfaService {
    constructor(private api: ApiService) {}

    getStatus() {
        return this.api.requestData<MfaStatus>('get', 'two_factor_auth/status');
    }

    verifyCode(code: string) {
        return this.api.requestType<{token: string}>('post', 'two_factor_auth/verify', {body: {code}});
    }

    getBackupCodes() {
        return this.api.requestData<string[]>('get', 'two_factor_auth/backup_codes');
    }

    generateBackupCodes() {
        return this.api.requestData<string[]>('post', 'two_factor_auth/backup_codes');
    }

    setupMfaRecovery() {
        return this.api.requestData<{backupCodes: string[]}>('post', 'two_factor_auth/setup');
    }

    setupMfaSecret() {
        return this.api.requestData<MfaSecret>('post', 'two_factor_auth/secret');
    }

    enableMfa(code: string) {
        let body = {code};
        return this.api.requestType<{token: string}>('post', 'two_factor_auth/enable', {body});
    }

    disableMfa() {
        return this.api.request('post', 'two_factor_auth/disable');
    }
}
