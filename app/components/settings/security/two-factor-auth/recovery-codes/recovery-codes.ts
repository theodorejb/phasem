import {Component, OnInit} from '@angular/core';
import {MfaService} from "../../../../../services/MfaService";

@Component({
    templateUrl: 'recovery-codes.html',
})
export class RecoveryCodesComponent implements OnInit {
    public error: string;
    public isLoading = true;
    public backupCodes: string[] = [];

    constructor(
        private mfaService: MfaService,
    ) {}

    ngOnInit() {
        this.mfaService.setupMfaRecovery()
            .subscribe(
                recovery => {
                    this.backupCodes = recovery.backupCodes;
                },
                error => {this.error = error;},
            )
            .add(() => {this.isLoading = false;});
    }
}
