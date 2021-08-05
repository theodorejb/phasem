import {Component, OnInit} from '@angular/core';
import {MfaStatus} from "../../../models/MFA";
import {MfaService} from "../../../services/MfaService";

@Component({
    templateUrl: 'security.html',
})
export class SecurityComponent implements OnInit {
    public error: string;
    public isLoading = true;
    public status: MfaStatus;

    constructor(
        private mfaService: MfaService,
    ) {}

    ngOnInit() {
        this.mfaService.getStatus()
            .subscribe({
                next: status => {this.status = status;},
                error: error => {this.error = error;},
            })
            .add(() => {this.isLoading = false;});
    }
}
