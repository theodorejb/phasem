import {Component, OnInit} from '@angular/core';
import {AuthService} from "../../services/AuthService";

@Component({
    templateUrl: 'logout.html',
})
export class LogOutComponent implements OnInit {
    public error: string;
    public success: boolean | null = null;

    constructor(private authService: AuthService) {}

    ngOnInit() {
        this.authService.logOut()
            .subscribe({
                next: result => {this.success = result;},
                error: error => {this.error = error;},
            });
    }
}
