import {Component, OnInit} from '@angular/core';
import {MfaService} from "../../../../services/MfaService";

@Component({
    templateUrl: 'two-factor-recovery.html',
})
export class TwoFactorRecoveryComponent implements OnInit {
    public error: string;
    public isLoading = true;
    public isGenerating: boolean = false;
    public backupCodes: string[] = [];

    constructor(
        private mfaService: MfaService,
    ) {}

    ngOnInit() {
        this.mfaService.getBackupCodes()
            .subscribe(
                backupCodes => {
                    this.backupCodes = backupCodes;
                },
                error => {this.error = error;},
            )
            .add(() => {this.isLoading = false;});
    }

    generateNewCodes() {
        this.isGenerating = true;

        this.mfaService.generateBackupCodes()
            .subscribe(
                backupCodes => {this.backupCodes = backupCodes;},
                error => {this.error = error;},
            )
            .add(() => {this.isGenerating = false;});
    }
}
