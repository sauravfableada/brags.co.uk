 <?php if ( empty( $this->hide_sidebar ) ) : ?>
    <div id="poststuff" class="dokan-reports-wrap">
        <div class="dokan-reports-sidebar report-left dokan-left">
            <?php if ( $legends = $this->get_chart_legend() ) : ?>
                <ul class="chart-legend">
                    <?php foreach ( $legends as $legend ) : ?>
                        <?php // @codingStandardsIgnoreStart ?>
                        <li style="border-color: <?php echo $legend['color']; ?>" <?php if ( isset( $legend['highlight_series'] ) ) echo 'class="highlight_series ' . ( isset( $legend['placeholder'] ) ? 'tips' : '' ) . '" data-series="' . esc_attr( $legend['highlight_series'] ) . '"'; ?> data-tip="<?php echo isset( $legend['placeholder'] ) ? $legend['placeholder'] : ''; ?>">
                            <?php echo $legend['title']; ?>
                        </li>
                        <?php // @codingStandardsIgnoreEnd ?>
                    <?php endforeach; ?>
                </ul>
                <ul class="chart-widgets">
                    <?php foreach ( $this->get_chart_widgets() as $widget ) : ?>
                        <li class="chart-widget">
                            <?php if ( $widget['title'] ) : ?>
                                <h4><?php echo esc_html( $widget['title'] ); ?></h4>
                            <?php endif; ?>
                            <?php call_user_func( $widget['callback'] ); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="dokan-reports-main report-right dokan-right">
            <div class="postbox">
                <?php
                if ( ! empty( $this->heading )   ) {
                    echo "<h3><span>$this->heading</span></h3>";
                }
                ?>
                <?php $this->get_main_chart(); ?>
            </div>
        </div>
    </div>
 <?php else : ?>
     <?php $this->get_main_chart(); ?>
 <?php endif; ?>
