import {AfterViewInit, Component, ElementRef, Input, OnChanges, OnDestroy, SimpleChanges, ViewChild} from '@angular/core';
import {Spinner} from 'spin.js';

@Component({
    selector: 'load-contents',
    template: `
        <div [ngStyle]="{display: loading ? 'block' : 'none', padding: '4em'}" #loader></div>
        <div [ngStyle]="{display: loading ? 'none' : 'block'}">
          <ng-content></ng-content>
        </div>
    `,
    styles: [`
        @keyframes load-content-spinner {
            0%, 39%, 100% {
                opacity: 0.25; /* minimum opacity */
            }
            40% {
                opacity: 1;
            }
        }
    `],
})
export class LoadContentsComponent implements AfterViewInit, OnChanges, OnDestroy {
    @Input() loading: boolean;
    @ViewChild('loader') loader: ElementRef;
    private spinner: Spinner;

    ngAfterViewInit() {
        this.spinner = new Spinner({
            lines: 13,
            length: 11,
            width: 4,
            radius: 17,
            animation: 'load-content-spinner',
            top: '0',
            left: '50%',
            position: 'relative',
        }).spin(this.loader.nativeElement);
    }

    ngOnChanges(changes: SimpleChanges) {
        if (!this.spinner) {
            return;
        }

        if (this.loading) {
            this.spinner.spin(this.loader.nativeElement);
        } else {
            this.spinner.stop();
        }
    }

    ngOnDestroy() {
        if (this.spinner) {
            this.spinner.stop();
        }
    }
}
